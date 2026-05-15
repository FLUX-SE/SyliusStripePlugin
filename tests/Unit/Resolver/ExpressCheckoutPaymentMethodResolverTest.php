<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Resolver;

use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolver;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;

final class ExpressCheckoutPaymentMethodResolverTest extends TestCase
{
    private const SUPPORTED_FACTORIES = ['stripe_checkout', 'stripe_web_elements'];

    public function test_it_returns_null_when_no_enabled_payment_methods_for_channel(): void
    {
        $resolver = $this->createResolver([]);

        self::assertNull($resolver->resolveForChannel($this->createMock(ChannelInterface::class)));
    }

    public function test_it_ignores_payment_methods_without_gateway_config(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn(null);

        $resolver = $this->createResolver([$paymentMethod]);

        self::assertNull($resolver->resolveForChannel($this->createMock(ChannelInterface::class)));
    }

    public function test_it_ignores_payment_methods_with_unsupported_factory_name(): void
    {
        $paymentMethod = $this->createPaymentMethod('paypal_express', ['enable_express_checkout' => true]);

        $resolver = $this->createResolver([$paymentMethod]);

        self::assertNull($resolver->resolveForChannel($this->createMock(ChannelInterface::class)));
    }

    public function test_it_ignores_payment_methods_with_express_checkout_disabled(): void
    {
        $paymentMethod = $this->createPaymentMethod('stripe_web_elements', ['enable_express_checkout' => false]);

        $resolver = $this->createResolver([$paymentMethod]);

        self::assertNull($resolver->resolveForChannel($this->createMock(ChannelInterface::class)));
    }

    public function test_it_ignores_payment_methods_without_express_checkout_config_key(): void
    {
        $paymentMethod = $this->createPaymentMethod('stripe_web_elements', []);

        $resolver = $this->createResolver([$paymentMethod]);

        self::assertNull($resolver->resolveForChannel($this->createMock(ChannelInterface::class)));
    }

    public function test_it_returns_a_single_eligible_stripe_checkout_payment_method(): void
    {
        $paymentMethod = $this->createPaymentMethod('stripe_checkout', ['enable_express_checkout' => true]);

        $resolver = $this->createResolver([$paymentMethod]);

        self::assertSame($paymentMethod, $resolver->resolveForChannel($this->createMock(ChannelInterface::class)));
    }

    public function test_it_prefers_stripe_web_elements_when_both_are_eligible(): void
    {
        $checkout = $this->createPaymentMethod('stripe_checkout', ['enable_express_checkout' => true]);
        $webElements = $this->createPaymentMethod('stripe_web_elements', ['enable_express_checkout' => true]);

        $resolver = $this->createResolver([$checkout, $webElements]);

        self::assertSame($webElements, $resolver->resolveForChannel($this->createMock(ChannelInterface::class)));
    }

    public function test_it_falls_back_to_first_candidate_when_web_elements_is_not_eligible(): void
    {
        $disabledWebElements = $this->createPaymentMethod('stripe_web_elements', ['enable_express_checkout' => false]);
        $checkout = $this->createPaymentMethod('stripe_checkout', ['enable_express_checkout' => true]);

        $resolver = $this->createResolver([$disabledWebElements, $checkout]);

        self::assertSame($checkout, $resolver->resolveForChannel($this->createMock(ChannelInterface::class)));
    }

    public function test_it_returns_publishable_key_from_gateway_config(): void
    {
        $paymentMethod = $this->createPaymentMethod('stripe_web_elements', [
            'enable_express_checkout' => true,
            'publishable_key' => 'pk_test_abc',
        ]);

        $resolver = $this->createResolver([]);

        self::assertSame('pk_test_abc', $resolver->getPublishableKey($paymentMethod));
    }

    public function test_it_returns_null_publishable_key_when_missing_or_empty(): void
    {
        $resolver = $this->createResolver([]);

        $withoutKey = $this->createPaymentMethod('stripe_web_elements', ['enable_express_checkout' => true]);
        $withEmptyKey = $this->createPaymentMethod('stripe_web_elements', ['enable_express_checkout' => true, 'publishable_key' => '']);

        self::assertNull($resolver->getPublishableKey($withoutKey));
        self::assertNull($resolver->getPublishableKey($withEmptyKey));
    }

    /** @param list<PaymentMethodInterface> $paymentMethods */
    private function createResolver(array $paymentMethods): ExpressCheckoutPaymentMethodResolver
    {
        $repository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $repository->method('findEnabledForChannel')->willReturn($paymentMethods);

        return new ExpressCheckoutPaymentMethodResolver($repository, self::SUPPORTED_FACTORIES);
    }

    /** @param array<string, mixed> $config */
    private function createPaymentMethod(string $factoryName, array $config): PaymentMethodInterface
    {
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getFactoryName')->willReturn($factoryName);
        $gatewayConfig->method('getConfig')->willReturn($config);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        return $paymentMethod;
    }
}
