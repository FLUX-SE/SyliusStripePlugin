<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Normalizer;

use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizer;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
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

    public function test_it_normalizes_google_pay_shipping_address(): void
    {
        $address = $this->normalizer->normalizeShipping([
            'expressPaymentType' => ExpressCheckoutAddressNormalizerInterface::TYPE_GOOGLE_PAY,
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

    public function test_it_handles_google_pay_address_without_line2(): void
    {
        $address = $this->normalizer->normalizeShipping([
            'expressPaymentType' => ExpressCheckoutAddressNormalizerInterface::TYPE_GOOGLE_PAY,
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

    public function test_it_throws_logic_exception_for_apple_pay_shipping(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Apple Pay/');

        $this->normalizer->normalizeShipping([
            'expressPaymentType' => ExpressCheckoutAddressNormalizerInterface::TYPE_APPLE_PAY,
        ]);
    }

    public function test_it_throws_for_unknown_express_payment_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported express payment type "unknown"/');

        $this->normalizer->normalizeShipping(['expressPaymentType' => 'unknown']);
    }

    public function test_it_throws_when_express_payment_type_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/expressPaymentType/');

        $this->normalizer->normalizeShipping([]);
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

    public function test_it_normalizes_a_flat_partial_address(): void
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
        self::assertNull($address->getStreet());
        self::assertNull($address->getFirstName());
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
