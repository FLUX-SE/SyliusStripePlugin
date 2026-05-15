<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Functional\Controller\Shop\ExpressCheckout;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ExpressCheckoutCsrf;
use Stripe\ApiResource;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\PaymentIntentMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

final class ConfirmActionTest extends WebTestCase
{
    private const URI = '/express-checkout/confirm';

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

    public function test_it_returns_forbidden_when_csrf_token_is_missing(): void
    {
        $this->loadFixtures(['channel.yaml']);

        $this->postJson($this->validPayload());

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_forbidden_when_csrf_token_is_invalid(): void
    {
        $this->loadFixtures(['channel.yaml']);
        $this->bootSession();

        $this->postJson($this->validPayload(), 'invalid_csrf_token');

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_unprocessable_entity_when_no_cart_exists(): void
    {
        $this->loadFixtures(['channel.yaml']);
        $token = $this->bootSession();

        $this->postJson($this->validPayload(), $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_unprocessable_entity_when_express_checkout_is_disabled_for_channel(): void
    {
        $this->loadFixtures([
            'channel.yaml',
            'payment_method.yaml',
        ]);
        $token = $this->bootSession();

        $this->postJson($this->validPayload(), $token);

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
        $token = $this->bootSession();

        $payload = $this->validPayload();
        unset($payload['billingDetails']);

        $this->postJson($payload, $token);

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
        $token = $this->bootSession($cart);

        // Stripe API call (PaymentIntent::create) is intercepted by
        // StripeClientWithExpectations decorator (already wired through Behat services.xml
        // which TestApplication imports for the test env). PaymentIntentMocker primes the
        // canned response with `client_secret: '1234567890'`.
        $this->getPaymentIntentMocker()->mockCreateAction();

        $this->postJson($this->validPayload(), $token);

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

    public function test_it_completes_a_digital_only_cart_without_shipping_step(): void
    {
        $fixtures = $this->loadFixtures([
            'channel.yaml',
            'tax_category.yaml',
            'express_checkout/payment_method.yaml',
            'express_checkout/cart_digital_ready.yaml',
        ]);

        /** @var OrderInterface $cart */
        $cart = $fixtures['cart_digital_ready'];
        $token = $this->bootSession($cart);

        $this->getPaymentIntentMocker()->mockCreateAction();

        // Digital-only payload: wallet popup was opened with shippingAddressRequired:false
        // (configured by ConfigurationProvider when $cart->isShippingRequired() === false),
        // so Stripe Element omits shippingAddress and shippingRate from the confirm event.
        $this->postJson([
            'expressPaymentType' => 'google_pay',
            'billingDetails' => [
                'email' => 'jane@example.com',
                'name' => 'Jane Doe',
                'phone' => '+1-555-0100',
                'address' => [
                    'line1' => '500 Terry A Francois Blvd',
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'postal_code' => '94158',
                    'country' => 'US',
                ],
            ],
        ], $token);

        $response = $this->client->getResponse();
        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            sprintf('Response body: %s', $response->getContent()),
        );

        $body = $this->decodeResponseBody();
        self::assertSame('1234567890', $body['clientSecret']);

        $this->entityManager->clear();
        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = static::getContainer()->get('sylius.repository.order');
        $placedOrder = $orderRepository->find($cart->getId());
        self::assertNotNull($placedOrder);
        self::assertSame('new', $placedOrder->getState());
        // Digital cart never reaches shipping_selected — OrderShipmentProcessor doesn't
        // create a Shipment for variants with shipping_required = false, and Sylius's
        // sylius_skip_shipping after-callback on TRANSITION_ADDRESS moves the checkout
        // state to shipping_skipped (then select_payment → completed).
        self::assertCount(0, $placedOrder->getShipments());
        self::assertSame('Jane', $placedOrder->getBillingAddress()?->getFirstName());
        self::assertNull($placedOrder->getShippingAddress());
    }

    public function test_it_returns_unprocessable_entity_on_double_confirm(): void
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
        $token = $this->bootSession($cart);

        $this->getPaymentIntentMocker()->mockCreateAction();

        $this->postJson($this->validPayload(), $token);
        self::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            sprintf('First confirm: %s', $this->client->getResponse()->getContent()),
        );

        // Second POST must not reach Stripe and must not raise a StateMachineExecutionException
        // (which would surface as 500). For a sequential second request Sylius's CartContext
        // already issued a fresh empty cart — so the rejection comes through the empty()
        // guard rather than alreadyPlaced(). The race-condition path (two parallel requests
        // hitting the same cart id) is covered by the defensive catch inside applyTransition().
        $this->postJson($this->validPayload(), $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->decodeResponseBody();
        self::assertArrayHasKey('error', $body);
    }

    /**
     * Creates a session for the BrowserKit client and pre-generates a CSRF token
     * inside it (via a temporary RequestStack push so CsrfTokenManager writes to
     * the same SessionInterface that the next HTTP request will load).
     *
     * Returns the randomized token value to send in the X-CSRF-Token header.
     */
    private function bootSession(?OrderInterface $cart = null): string
    {
        /** @var SessionFactoryInterface $sessionFactory */
        $sessionFactory = static::getContainer()->get('session.factory');
        $session = $sessionFactory->createSession();

        if (null !== $cart) {
            $channel = $cart->getChannel();
            self::assertNotNull($channel);
            $session->set('_sylius.cart.' . $channel->getCode(), $cart->getId());
        }

        $token = $this->generateCsrfToken($session);

        $session->save();
        $this->client->getCookieJar()->set(
            new Cookie($session->getName(), $session->getId()),
        );

        return $token;
    }

    private function generateCsrfToken(SessionInterface $session): string
    {
        /** @var RequestStack $requestStack */
        $requestStack = static::getContainer()->get('request_stack');
        $request = new Request();
        $request->setSession($session);
        $requestStack->push($request);

        try {
            /** @var CsrfTokenManagerInterface $tokenManager */
            $tokenManager = static::getContainer()->get('security.csrf.token_manager');

            return $tokenManager->getToken(ExpressCheckoutCsrf::TOKEN_ID)->getValue();
        } finally {
            $requestStack->pop();
        }
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

    /**
     * @param array<string, mixed> $body
     */
    private function postJson(array $body, ?string $csrfToken = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if (null !== $csrfToken) {
            $server['HTTP_X_CSRF_TOKEN'] = $csrfToken;
        }
        $this->client->request(
            method: 'POST',
            uri: self::URI,
            server: $server,
            content: json_encode($body, \JSON_THROW_ON_ERROR),
        );
    }
}
