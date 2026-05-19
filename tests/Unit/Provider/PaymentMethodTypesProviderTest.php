<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider;

use FluxSE\SyliusStripePlugin\Provider\PaymentMethodTypesProvider;
use PHPUnit\Framework\TestCase;
use Stripe\ApiResource;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class PaymentMethodTypesProviderTest extends TestCase
{
    /** @var PaymentMethodTypesProvider<ApiResource> */
    private PaymentMethodTypesProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new PaymentMethodTypesProvider();
    }

    public function test_does_nothing_when_payment_method_types_is_empty(): void
    {
        $paymentRequest = $this->createPaymentRequest(['payment_method_types' => []]);
        $params = [];

        $deprecations = $this->captureDeprecations(
            function () use ($paymentRequest, &$params): void {
                $this->provider->provide($paymentRequest, $params);
            },
        );

        self::assertSame([], $params);
        self::assertSame([], $deprecations);
    }

    public function test_does_nothing_when_payment_method_types_key_missing(): void
    {
        $paymentRequest = $this->createPaymentRequest([]);
        $params = [];

        $deprecations = $this->captureDeprecations(
            function () use ($paymentRequest, &$params): void {
                $this->provider->provide($paymentRequest, $params);
            },
        );

        self::assertSame([], $params);
        self::assertSame([], $deprecations);
    }

    public function test_does_nothing_when_gateway_config_is_null(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn(null);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getMethod')->willReturn($paymentMethod);

        $params = [];

        $deprecations = $this->captureDeprecations(
            function () use ($paymentRequest, &$params): void {
                $this->provider->provide($paymentRequest, $params);
            },
        );

        self::assertSame([], $params);
        self::assertSame([], $deprecations);
    }

    public function test_sets_param_and_triggers_deprecation_when_payment_method_types_is_non_empty(): void
    {
        $paymentRequest = $this->createPaymentRequest(['payment_method_types' => ['card', 'ideal']]);
        $params = [];

        $deprecations = $this->captureDeprecations(
            function () use ($paymentRequest, &$params): void {
                $this->provider->provide($paymentRequest, $params);
            },
        );

        self::assertSame(['payment_method_types' => ['card', 'ideal']], $params);
        self::assertCount(1, $deprecations);
        self::assertStringContainsString('payment_method_types', $deprecations[0]);
        self::assertStringContainsString('deprecated', $deprecations[0]);
        self::assertStringContainsString('2.0', $deprecations[0]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createPaymentRequest(array $config): PaymentRequestInterface
    {
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getConfig')->willReturn($config);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getMethod')->willReturn($paymentMethod);

        return $paymentRequest;
    }

    /**
     * @return list<string>
     */
    private function captureDeprecations(callable $callback): array
    {
        $captured = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$captured): bool {
            $captured[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $captured;
    }
}
