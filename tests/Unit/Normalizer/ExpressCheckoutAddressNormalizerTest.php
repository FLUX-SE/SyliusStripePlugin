<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Normalizer;

use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizer;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class ExpressCheckoutAddressNormalizerTest extends TestCase
{
    private ExpressCheckoutAddressNormalizer $normalizer;

    protected function setUp(): void
    {
        $factory = $this->createMock(FactoryInterface::class);
        $factory->method('createNew')->willReturnCallback(static fn (): Address => new Address());

        $this->normalizer = new ExpressCheckoutAddressNormalizer($factory);
    }

    public function test_it_normalizes_a_shipping_address_payload(): void
    {
        $address = $this->normalizer->normalizeShipping([
            'shippingAddress' => [
                'name' => 'Jane Doe',
                'address' => [
                    'line1' => '1 Infinite Loop',
                    'line2' => 'Apt 5',
                    'city' => 'Cupertino',
                    'state' => 'CA',
                    'postal_code' => '95014',
                    'country' => 'US',
                ],
                'phone' => '+1-555-0100',
            ],
        ]);

        self::assertSame('Jane', $address->getFirstName());
        self::assertSame('Doe', $address->getLastName());
        self::assertSame('1 Infinite Loop Apt 5', $address->getStreet());
        self::assertSame('Cupertino', $address->getCity());
        self::assertSame('95014', $address->getPostcode());
        self::assertSame('CA', $address->getProvinceName());
        self::assertSame('US', $address->getCountryCode());
        self::assertSame('+1-555-0100', $address->getPhoneNumber());
    }

    public function test_it_handles_shipping_address_without_line2(): void
    {
        $address = $this->normalizer->normalizeShipping([
            'shippingAddress' => [
                'name' => 'Madonna',
                'address' => [
                    'line1' => '1 Infinite Loop',
                    'city' => 'Cupertino',
                    'country' => 'US',
                ],
            ],
        ]);

        self::assertSame('Madonna', $address->getFirstName());
        self::assertNull($address->getLastName());
        self::assertSame('1 Infinite Loop', $address->getStreet());
    }

    public function test_it_throws_when_shipping_address_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/shippingAddress/');

        $this->normalizer->normalizeShipping([]);
    }

    public function test_it_normalizes_a_flat_partial_address_and_keeps_placeholders_for_not_null_fields(): void
    {
        $address = $this->normalizer->normalizeAddress([
            'city' => 'Cupertino',
            'state' => 'CA',
            'postal_code' => '95014',
            'country' => 'US',
        ]);

        self::assertSame('Cupertino', $address->getCity());
        self::assertSame('CA', $address->getProvinceName());
        self::assertSame('95014', $address->getPostcode());
        self::assertSame('US', $address->getCountryCode());
        // Fields not present in the partial wallet payload keep the placeholder set in
        // createAddress(), so Doctrine's NOT NULL constraints on first_name/last_name/street
        // are satisfied when the cart is flushed during shipping-rate preview.
        self::assertSame('?', $address->getFirstName());
        self::assertSame('?', $address->getLastName());
        self::assertSame('?', $address->getStreet());
    }

    public function test_it_returns_clone_of_shipping_when_billing_details_missing(): void
    {
        $shipping = new Address();
        $shipping->setCity('Cupertino');

        $billing = $this->normalizer->normalizeBilling([], $shipping);

        self::assertNotSame($shipping, $billing);
        self::assertSame('Cupertino', $billing->getCity());
    }

    public function test_it_returns_clone_of_shipping_when_billing_address_is_incomplete(): void
    {
        $shipping = new Address();
        $shipping->setCity('Cupertino');

        $billing = $this->normalizer->normalizeBilling([
            'billingDetails' => [
                'address' => [
                    'line1' => '1 Apple Park Way',
                    // missing country
                ],
            ],
        ], $shipping);

        self::assertSame('Cupertino', $billing->getCity());
    }

    public function test_it_uses_billing_details_when_address_is_complete(): void
    {
        $shipping = new Address();
        $shipping->setCity('Cupertino');

        $billing = $this->normalizer->normalizeBilling([
            'billingDetails' => [
                'name' => 'John Smith',
                'phone' => '+1-555-0199',
                'address' => [
                    'line1' => '1 Apple Park Way',
                    'city' => 'Cupertino',
                    'postal_code' => '95014',
                    'country' => 'US',
                ],
            ],
        ], $shipping);

        self::assertSame('John', $billing->getFirstName());
        self::assertSame('Smith', $billing->getLastName());
        self::assertSame('1 Apple Park Way', $billing->getStreet());
        self::assertSame('US', $billing->getCountryCode());
        self::assertSame('+1-555-0199', $billing->getPhoneNumber());
    }
}
