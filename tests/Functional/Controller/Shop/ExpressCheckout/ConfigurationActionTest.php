<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Functional\Controller\Shop\ExpressCheckout;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

final class ConfigurationActionTest extends WebTestCase
{
    private const URI = '/express-checkout/cart/configuration';

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
    }

    public function test_it_returns_no_content_when_no_express_checkout_payment_method_exists(): void
    {
        $this->loadFixtures(['channel.yaml']);

        $this->client->request('GET', self::URI);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_no_content_when_no_cart_exists(): void
    {
        $this->loadFixtures([
            'channel.yaml',
            'express_checkout/payment_method.yaml',
        ]);

        $this->client->request('GET', self::URI);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_200_with_publishable_key_when_cart_exists(): void
    {
        $fixtures = $this->loadFixtures([
            'channel.yaml',
            'tax_category.yaml',
            'shipping_category.yaml',
            'product_variant.yaml',
            'express_checkout/payment_method.yaml',
            'express_checkout/cart_ready.yaml',
        ]);

        /** @var OrderInterface $cart */
        $cart = $fixtures['cart_ready'];
        $this->startCartSession($cart);

        $this->client->request('GET', self::URI);

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);
        self::assertSame('pk_test_ece_123', $body['publishableKey']);
        self::assertSame('STRIPE_WEB_ELEMENTS_ECE', $body['paymentMethodCode']);
        self::assertSame('usd', $body['currency']);
        self::assertArrayHasKey('amount', $body);
        self::assertIsInt($body['amount']);
        self::assertTrue($body['shippingRequired']);
        self::assertArrayHasKey('allowedCountryCodes', $body);
        self::assertIsArray($body['allowedCountryCodes']);
    }

    public function test_it_returns_shipping_required_false_for_a_digital_only_cart(): void
    {
        $fixtures = $this->loadFixtures([
            'channel.yaml',
            'tax_category.yaml',
            'express_checkout/payment_method.yaml',
            'express_checkout/cart_digital_ready.yaml',
        ]);

        /** @var OrderInterface $cart */
        $cart = $fixtures['cart_digital_ready'];
        $this->startCartSession($cart);

        $this->client->request('GET', self::URI);

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);
        self::assertFalse($body['shippingRequired']);
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
}
