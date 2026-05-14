<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Functional\Controller\Shop\ExpressCheckout;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Stripe\ApiResource;
use Stripe\Event;
use Stripe\PaymentIntent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\EventMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\PaymentIntentMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

/**
 * Full E2E coverage for the Express Checkout payment lifecycle:
 *
 * 1. `POST /express-checkout/cart/confirm` creates a `PaymentIntent` and persists a
 *    capture `PaymentRequest` with the client_secret.
 * 2. `POST /payment-methods/{code}` with a signed `payment_intent.succeeded` event
 *    re-fetches the Stripe Event + PaymentIntent server-side and drives the Sylius
 *    state machines so the `Payment` transitions to `completed` and the `Order`'s
 *    payment state moves to `paid`.
 *
 * The second test exercises the cross-factory routing fix (commit d7dd971): the
 * `payment_intent.succeeded` webhook lands on a `stripe_checkout`-factory PaymentMethod
 * with `enable_express_checkout: true`, and the composite processors / command provider
 * select the PaymentIntent stack (not the Checkout Session stack) based on
 * `Payment.details.object`.
 */
final class PaymentLifecycleTest extends WebTestCase
{
    private const CONFIRM_URI = '/express-checkout/cart/confirm';

    private const WEBHOOK_SECRET = 'whsec_test_ece_123';

    private KernelBrowser $client;

    private PurgerLoader $fixtureLoader;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        /** @var PurgerLoader $loader */
        $loader = $container->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->fixtureLoader = $loader;
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $this->purgeDatabase();
        $this->getStripeClientWithExpectations()->resetExpectations();
    }

    public function test_it_completes_payment_when_webhook_arrives_for_web_elements_factory(): void
    {
        $this->runFullLifecycle(
            paymentMethodFixture: 'express_checkout/payment_method.yaml',
            paymentMethodCode: 'STRIPE_WEB_ELEMENTS_ECE',
        );
    }

    public function test_it_completes_payment_when_webhook_arrives_for_stripe_checkout_factory_with_ece_on(): void
    {
        $this->runFullLifecycle(
            paymentMethodFixture: 'express_checkout/payment_method_stripe_checkout.yaml',
            paymentMethodCode: 'STRIPE_CHECKOUT_ECE',
        );
    }

    private function runFullLifecycle(string $paymentMethodFixture, string $paymentMethodCode): void
    {
        $fixtures = $this->loadFixtures([
            'channel.yaml',
            'tax_category.yaml',
            'shipping_category.yaml',
            'product_variant.yaml',
            'shipping_method.yaml',
            $paymentMethodFixture,
            'express_checkout/cart_ready.yaml',
        ]);

        /** @var OrderInterface $cart */
        $cart = $fixtures['cart_ready'];
        $cartId = $cart->getId();
        $this->startCartSession($cart);

        $this->getPaymentIntentMocker()->mockCreateAction();

        $this->postJson(self::CONFIRM_URI, $this->validConfirmPayload());

        $confirmResponse = $this->client->getResponse();
        self::assertSame(
            Response::HTTP_OK,
            $confirmResponse->getStatusCode(),
            sprintf('Confirm response body: %s', $confirmResponse->getContent()),
        );

        $this->entityManager->clear();
        $capturePaymentRequest = $this->findLatestCapturePaymentRequest();
        $confirmPaymentId = $capturePaymentRequest->getPayment()->getId();

        // The persisted Payment.details object dictates the cross-factory routing
        // in CheckoutOrPaymentIntentTransitionProcessor / CommandProvider.
        self::assertSame(
            PaymentIntent::OBJECT_NAME,
            $capturePaymentRequest->getPayment()->getDetails()['object'] ?? null,
            'Payment.details should hold a PaymentIntent regardless of the configured factory.',
        );

        $eventJson = $this->buildSucceededEventPayload((string) $capturePaymentRequest->getId());
        $this->getEventMocker()->mockRetrieveAction($eventJson);
        $this->getPaymentIntentMocker()->mockRetrieveAction([
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
            'metadata' => [
                'token_hash' => (string) $capturePaymentRequest->getId(),
            ],
        ]);

        $payload = json_encode($eventJson, \JSON_THROW_ON_ERROR);
        $this->postSignedWebhook($paymentMethodCode, $payload);

        $webhookResponse = $this->client->getResponse();
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $webhookResponse->getStatusCode(),
            sprintf('Webhook response body: %s', $webhookResponse->getContent()),
        );

        $this->entityManager->clear();
        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = static::getContainer()->get('sylius.repository.order');
        $order = $orderRepository->find($cartId);
        self::assertNotNull($order);

        $payment = $this->findPaymentById($order, $confirmPaymentId);
        self::assertNotNull(
            $payment,
            sprintf('Payment id=%s should still exist on the order.', (string) $confirmPaymentId),
        );

        self::assertSame(PaymentInterface::STATE_COMPLETED, $payment->getState());
        self::assertSame(PaymentIntent::STATUS_SUCCEEDED, $payment->getDetails()['status'] ?? null);
        self::assertSame(OrderPaymentStates::STATE_PAID, $order->getPaymentState());
    }

    private function findPaymentById(OrderInterface $order, mixed $paymentId): ?PaymentInterface
    {
        foreach ($order->getPayments() as $payment) {
            if ($payment->getId() === $paymentId) {
                return $payment;
            }
        }

        return null;
    }

    private function findLatestCapturePaymentRequest(): PaymentRequestInterface
    {
        /** @var PaymentRequestRepositoryInterface<PaymentRequestInterface> $repository */
        $repository = static::getContainer()->get('sylius.repository.payment_request');
        $paymentRequests = $repository->findBy(['action' => PaymentRequestInterface::ACTION_CAPTURE]);

        self::assertCount(1, $paymentRequests, 'Expected exactly one capture PaymentRequest after confirm.');

        return $paymentRequests[0];
    }

    /** @return array<string, mixed> */
    private function buildSucceededEventPayload(string $tokenHash): array
    {
        return [
            'id' => 'evt_test_1',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            'data' => [
                'object' => [
                    'id' => 'pi_test_1',
                    'object' => PaymentIntent::OBJECT_NAME,
                    'status' => PaymentIntent::STATUS_SUCCEEDED,
                    'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
                    'metadata' => [
                        'token_hash' => $tokenHash,
                    ],
                ],
            ],
        ];
    }

    private function postSignedWebhook(string $paymentMethodCode, string $payload): void
    {
        $timestamp = time();
        $signedPayload = sprintf('%s.%s', $timestamp, $payload);
        $signature = hash_hmac('sha256', $signedPayload, self::WEBHOOK_SECRET);
        $sigHeader = sprintf('t=%d,v1=%s', $timestamp, $signature);

        $this->client->request(
            method: 'POST',
            uri: sprintf('/payment-methods/%s', $paymentMethodCode),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            ],
            content: $payload,
        );
    }

    private function startCartSession(OrderInterface $cart): void
    {
        /** @var SessionFactoryInterface $sessionFactory */
        $sessionFactory = static::getContainer()->get('session.factory');
        $session = $sessionFactory->createSession();
        $channel = $cart->getChannel();
        self::assertNotNull($channel);
        $session->set('_sylius.cart.' . $channel->getCode(), $cart->getId());
        $session->save();

        $this->client->getCookieJar()->set(
            new Cookie($session->getName(), $session->getId()),
        );
    }

    private function getPaymentIntentMocker(): PaymentIntentMocker
    {
        /** @var PaymentIntentMocker $mocker */
        $mocker = static::getContainer()->get(PaymentIntentMocker::class);

        return $mocker;
    }

    private function getEventMocker(): EventMocker
    {
        /** @var EventMocker $mocker */
        $mocker = static::getContainer()->get(EventMocker::class);

        return $mocker;
    }

    /** @return StripeClientWithExpectationsInterface<ApiResource> */
    private function getStripeClientWithExpectations(): StripeClientWithExpectationsInterface
    {
        /** @var StripeClientWithExpectationsInterface<ApiResource> $client */
        $client = static::getContainer()->get('flux_se.sylius_stripe.stripe.http_client');

        return $client;
    }

    /** @return array<string, mixed> */
    private function validConfirmPayload(): array
    {
        return [
            'expressPaymentType' => 'google_pay',
            'shippingAddress' => [
                'name' => 'Jane Doe',
                'address' => [
                    'line1' => '1 Infinite Loop',
                    'city' => 'Cupertino',
                    'state' => 'CA',
                    'postal_code' => '95014',
                    'country' => 'US',
                ],
            ],
            'billingDetails' => [
                'email' => 'jane@example.com',
                'name' => 'Jane Doe',
                'phone' => '+1-555-0100',
            ],
            'shippingRate' => ['id' => 'UPS'],
        ];
    }

    /** @param array<string, mixed> $body */
    private function postJson(string $uri, array $body): void
    {
        $this->client->request(
            method: 'POST',
            uri: $uri,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, \JSON_THROW_ON_ERROR),
        );
    }

    private function purgeDatabase(): void
    {
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();
        $this->entityManager->clear();
    }

    /**
     * @param list<string> $files
     *
     * @return array<string, object>
     */
    private function loadFixtures(array $files): array
    {
        $fixturesDir = __DIR__ . '/../../../DataFixtures/ORM';
        $resolved = [];
        foreach ($files as $file) {
            $resolved[] = sprintf('%s/%s', $fixturesDir, $file);
        }

        return $this->fixtureLoader->load($resolved);
    }
}
