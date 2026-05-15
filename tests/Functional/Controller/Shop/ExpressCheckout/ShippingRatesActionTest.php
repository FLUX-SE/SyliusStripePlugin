<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Functional\Controller\Shop\ExpressCheckout;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ExpressCheckoutCsrf;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ShippingRatesActionTest extends WebTestCase
{
    private const URI = '/express-checkout/cart/shipping-rates';

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

    public function test_it_returns_forbidden_when_csrf_token_is_missing(): void
    {
        $this->loadFixtures(['channel.yaml']);

        $this->postJson(['address' => ['country' => 'US', 'postal_code' => '95014']]);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_forbidden_when_csrf_token_is_invalid(): void
    {
        $this->loadFixtures(['channel.yaml']);
        $this->bootSession();

        $this->postJson(['address' => ['country' => 'US', 'postal_code' => '95014']], 'invalid_csrf_token');

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_unprocessable_entity_when_no_cart_exists(): void
    {
        $this->loadFixtures(['channel.yaml']);
        $token = $this->bootSession();

        $this->postJson(['address' => ['country' => 'US', 'postal_code' => '95014']], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function test_it_returns_unprocessable_entity_when_address_is_missing_from_payload(): void
    {
        $this->loadFixtures(['channel.yaml']);
        $token = $this->bootSession();

        $this->postJson([], $token);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('error', $body);
    }

    private function bootSession(): string
    {
        /** @var SessionFactoryInterface $sessionFactory */
        $sessionFactory = static::getContainer()->get('session.factory');
        $session = $sessionFactory->createSession();

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

    private function purgeDatabase(): void
    {
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $this->entityManager->clear();
    }

    /**
     * @param list<string> $files
     */
    private function loadFixtures(array $files): void
    {
        // ExpressCheckout → Shop → Controller → Functional, then DataFixtures/ORM
        $fixturesDir = __DIR__ . '/../../../DataFixtures/ORM';
        $resolved = [];
        foreach ($files as $file) {
            $resolved[] = sprintf('%s/%s', $fixturesDir, $file);
        }

        $this->fixtureLoader->load($resolved);
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
