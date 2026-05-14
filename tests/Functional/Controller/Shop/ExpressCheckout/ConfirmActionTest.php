<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Functional\Controller\Shop\ExpressCheckout;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Stripe\ApiResource;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\PaymentIntentMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

final class ConfirmActionTest extends WebTestCase
{
    private const URI = '/express-checkout/cart/confirm';

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

    public function test_it_returns_unprocessable_entity_when_no_cart_exists(): void
    {
        $this->loadFixtures(['channel.yaml']);

        $this->postJson($this->validPayload());

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_unprocessable_entity_when_express_checkout_is_disabled_for_channel(): void
    {
        $this->loadFixtures([
            'channel.yaml',
            'payment_method.yaml',
        ]);

        $this->postJson($this->validPayload());

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->decodeResponseBody();
        self::assertArrayHasKey('error', $body);
    }

    public function test_it_returns_unprocessable_entity_when_payload_is_missing_email(): void
    {
        $this->loadFixtures([
            'channel.yaml',
            'express_checkout/payment_method.yaml',
        ]);

        $payload = $this->validPayload();
        unset($payload['billingDetails']);

        $this->postJson($payload);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->decodeResponseBody();
        self::assertArrayHasKey('error', $body);
    }

    public function test_it_returns_200_and_creates_payment_intent(): void
    {
        $fixtures = $this->loadFixtures([
            'channel.yaml',
            'tax_category.yaml',
            'shipping_category.yaml',
            'product_variant.yaml',
            'shipping_method.yaml',
            'express_checkout/payment_method.yaml',
            'express_checkout/cart_ready.yaml',
        ]);

        /** @var OrderInterface $cart */
        $cart = $fixtures['cart_ready'];
        $this->startCartSession($cart);

        // Stripe API call (PaymentIntent::create) is intercepted by
        // StripeClientWithExpectations decorator (already wired through Behat services.xml
        // which TestApplication imports for the test env). PaymentIntentMocker primes the
        // canned response with `client_secret: '1234567890'`.
        $this->getPaymentIntentMocker()->mockCreateAction();

        $this->postJson($this->validPayload());

        $response = $this->client->getResponse();
        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            sprintf('Response body: %s', $response->getContent()),
        );

        $body = $this->decodeResponseBody();
        self::assertSame('1234567890', $body['clientSecret']);
        self::assertArrayHasKey('returnUrl', $body);
        self::assertIsString($body['returnUrl']);
        self::assertStringContainsString('/payment-request/pay/', $body['returnUrl']);

        // DB side-effects: cart transitioned to placed order, PaymentRequest created with
        // capture action + responseData.client_secret persisted.
        $this->entityManager->clear();
        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = static::getContainer()->get('sylius.repository.order');
        $placedOrder = $orderRepository->find($cart->getId());
        self::assertNotNull($placedOrder);
        self::assertSame('new', $placedOrder->getState());

        /** @var PaymentRequestRepositoryInterface<PaymentRequestInterface> $paymentRequestRepo */
        $paymentRequestRepo = static::getContainer()->get('sylius.repository.payment_request');
        $paymentRequests = $paymentRequestRepo->findAll();
        self::assertCount(1, $paymentRequests);
        self::assertSame(PaymentRequestInterface::ACTION_CAPTURE, $paymentRequests[0]->getAction());
        self::assertSame('1234567890', $paymentRequests[0]->getResponseData()['client_secret'] ?? null);
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

    /** @return StripeClientWithExpectationsInterface<ApiResource> */
    private function getStripeClientWithExpectations(): StripeClientWithExpectationsInterface
    {
        /** @var StripeClientWithExpectationsInterface<ApiResource> $client */
        $client = static::getContainer()->get('flux_se.sylius_stripe.stripe.http_client');

        return $client;
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
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

    /**
     * @return array<string, mixed>
     */
    private function decodeResponseBody(): array
    {
        $content = (string) $this->client->getResponse()->getContent();
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
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
        // ExpressCheckout → Shop → Controller → Functional, then DataFixtures/ORM
        $fixturesDir = __DIR__ . '/../../../DataFixtures/ORM';
        $resolved = [];
        foreach ($files as $file) {
            $resolved[] = sprintf('%s/%s', $fixturesDir, $file);
        }

        return $this->fixtureLoader->load($resolved);
    }

    /** @param array<string, mixed> $body */
    private function postJson(array $body): void
    {
        $this->client->request(
            method: 'POST',
            uri: self::URI,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, \JSON_THROW_ON_ERROR),
        );
    }
}
