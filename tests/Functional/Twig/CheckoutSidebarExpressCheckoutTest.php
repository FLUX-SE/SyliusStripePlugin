<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Functional\Twig;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

final class CheckoutSidebarExpressCheckoutTest extends WebTestCase
{
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

    public function test_it_renders_express_checkout_button_in_checkout_sidebar(): void
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

        $this->client->request('GET', '/en_US/checkout/address');

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = (string) $response->getContent();
        self::assertStringContainsString('data-sylius-stripe-express-checkout-checkout', $content);
        self::assertStringContainsString('data-sylius-stripe-express-checkout-mount', $content);
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
        $fixturesDir = __DIR__ . '/../DataFixtures/ORM';
        $resolved = [];
        foreach ($files as $file) {
            $resolved[] = sprintf('%s/%s', $fixturesDir, $file);
        }

        return $this->fixtureLoader->load($resolved);
    }
}
