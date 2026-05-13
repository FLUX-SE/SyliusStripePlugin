<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\ExpressCheckout\Payload;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ExpressCheckoutPayloadReaderTest extends TestCase
{
    private ExpressCheckoutPayloadReader $reader;

    protected function setUp(): void
    {
        $this->reader = new ExpressCheckoutPayloadReader();
    }

    public function test_it_returns_an_empty_payload_for_an_empty_request_body(): void
    {
        $payload = $this->reader->read(new Request(content: ''));

        self::assertSame([], $payload->raw());
        self::assertNull($payload->getAddress());
        self::assertNull($payload->getEmail());
        self::assertNull($payload->getShippingRateId());
    }

    public function test_it_returns_an_empty_payload_when_json_is_malformed(): void
    {
        $payload = $this->reader->read(new Request(content: '{not json'));

        self::assertSame([], $payload->raw());
    }

    public function test_it_returns_an_empty_payload_when_root_is_not_an_array(): void
    {
        $payload = $this->reader->read(new Request(content: '"just a string"'));

        self::assertSame([], $payload->raw());
    }

    public function test_it_reads_a_full_confirm_payload(): void
    {
        $body = json_encode([
            'billingDetails' => ['email' => 'shopper@example.com'],
            'shippingAddress' => ['name' => 'Jane Doe'],
            'shippingRate' => ['id' => 'ups_ground'],
        ], \JSON_THROW_ON_ERROR);

        $payload = $this->reader->read(new Request(content: $body));

        self::assertSame('shopper@example.com', $payload->getEmail());
        self::assertSame('ups_ground', $payload->getShippingRateId());
        self::assertNull($payload->getAddress());
    }

    public function test_it_reads_a_partial_address_change_payload(): void
    {
        $body = json_encode([
            'address' => ['country' => 'FR', 'postal_code' => '75001'],
            'shippingRateId' => 'ups_ground',
        ], \JSON_THROW_ON_ERROR);

        $payload = $this->reader->read(new Request(content: $body));

        self::assertSame(['country' => 'FR', 'postal_code' => '75001'], $payload->getAddress());
        self::assertSame('ups_ground', $payload->getShippingRateId());
        self::assertNull($payload->getEmail());
    }

    public function test_it_returns_null_for_empty_email_field(): void
    {
        $body = json_encode(['billingDetails' => ['email' => '']], \JSON_THROW_ON_ERROR);

        $payload = $this->reader->read(new Request(content: $body));

        self::assertNull($payload->getEmail());
    }

    public function test_it_returns_null_when_billing_details_is_not_an_array(): void
    {
        $body = json_encode(['billingDetails' => 'not-an-array'], \JSON_THROW_ON_ERROR);

        $payload = $this->reader->read(new Request(content: $body));

        self::assertNull($payload->getEmail());
    }
}
