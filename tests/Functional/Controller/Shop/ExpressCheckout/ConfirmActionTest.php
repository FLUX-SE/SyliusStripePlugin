<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Functional\Controller\Shop\ExpressCheckout;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * @return array<string, mixed>
     */
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
            'shippingRate' => ['id' => 'UPS_GROUND'],
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
