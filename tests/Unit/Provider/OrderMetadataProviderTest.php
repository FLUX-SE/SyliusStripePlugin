<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider;

use FluxSE\SyliusStripePlugin\Provider\OrderMetadataProvider;
use PHPUnit\Framework\TestCase;
use Stripe\PaymentIntent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class OrderMetadataProviderTest extends TestCase
{
    /** @var OrderMetadataProvider<PaymentIntent> */
    private OrderMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new OrderMetadataProvider();
    }

    public function test_it_populates_all_keys_when_order_is_complete(): void
    {
        $paymentRequest = $this->wrapOrder(
            number: '000000042',
            total: 4250,
            currencyCode: 'EUR',
            localeCode: 'de_DE',
        );

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame([
            'order_number' => '000000042',
            'order_total' => '4250',
            'currency' => 'EUR',
            'locale' => 'de_DE',
        ], $params);
    }

    public function test_it_omits_order_number_when_null(): void
    {
        $paymentRequest = $this->wrapOrder(
            number: null,
            total: 1500,
            currencyCode: 'USD',
            localeCode: 'en_US',
        );

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertArrayNotHasKey('order_number', $params);
        self::assertSame('1500', $params['order_total']);
        self::assertSame('USD', $params['currency']);
        self::assertSame('en_US', $params['locale']);
    }

    public function test_it_omits_currency_when_null(): void
    {
        $paymentRequest = $this->wrapOrder(
            number: 'X',
            total: 100,
            currencyCode: null,
            localeCode: 'en_US',
        );

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertArrayNotHasKey('currency', $params);
    }

    public function test_it_omits_locale_when_null(): void
    {
        $paymentRequest = $this->wrapOrder(
            number: 'X',
            total: 100,
            currencyCode: 'USD',
            localeCode: null,
        );

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertArrayNotHasKey('locale', $params);
    }

    public function test_it_returns_early_when_order_is_null(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn(null);
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        $params = ['untouched' => true];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['untouched' => true], $params);
    }

    private function wrapOrder(
        ?string $number,
        int $total,
        ?string $currencyCode,
        ?string $localeCode,
    ): PaymentRequestInterface {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getNumber')->willReturn($number);
        $order->method('getTotal')->willReturn($total);
        $order->method('getCurrencyCode')->willReturn($currencyCode);
        $order->method('getLocaleCode')->willReturn($localeCode);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        return $paymentRequest;
    }
}
