<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Notification;

use FluxSE\SyliusStripePlugin\Notification\LegacyStripeKeyNotificationProvider;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;

final class LegacyStripeKeyNotificationProviderTest extends TestCase
{
    private const STRIPE_FACTORY_NAMES = ['stripe_checkout', 'stripe_web_elements'];

    /** @var PaymentMethodRepositoryInterface<PaymentMethodInterface>&MockObject */
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;

    private GatewayFactoryNameProviderInterface&MockObject $gatewayFactoryNameProvider;

    private LegacyKeyDetectorInterface&MockObject $legacyKeyDetector;

    protected function setUp(): void
    {
        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->gatewayFactoryNameProvider = $this->createMock(GatewayFactoryNameProviderInterface::class);
        $this->legacyKeyDetector = $this->createMock(LegacyKeyDetectorInterface::class);
    }

    public function test_it_always_supports_notifications(): void
    {
        self::assertTrue($this->createProvider()->supports());
    }

    public function test_it_returns_no_notifications_when_no_payment_methods_exist(): void
    {
        $this->paymentMethodRepository
            ->method('findBy')
            ->with(['enabled' => true])
            ->willReturn([])
        ;

        self::assertSame([], $this->createProvider()->getNotifications());
    }

    public function test_it_ignores_non_stripe_payment_methods(): void
    {
        $paymentMethod = $this->paymentMethodWithGatewayConfig(['secret_key' => 'sk_live_abc']);
        $this->paymentMethodRepository->method('findBy')->willReturn([$paymentMethod]);
        $this->gatewayFactoryNameProvider->method('provide')->with($paymentMethod)->willReturn('offline');
        $this->legacyKeyDetector->expects(self::never())->method('isLegacy');

        self::assertSame([], $this->createProvider()->getNotifications());
    }

    public function test_it_skips_payment_methods_without_gateway_config(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn(null);
        $this->paymentMethodRepository->method('findBy')->willReturn([$paymentMethod]);
        $this->gatewayFactoryNameProvider->expects(self::never())->method('provide');

        self::assertSame([], $this->createProvider()->getNotifications());
    }

    public function test_it_skips_stripe_payment_methods_using_restricted_keys(): void
    {
        $paymentMethod = $this->paymentMethodWithGatewayConfig(['secret_key' => 'rk_live_abc']);
        $this->paymentMethodRepository->method('findBy')->willReturn([$paymentMethod]);
        $this->gatewayFactoryNameProvider->method('provide')->with($paymentMethod)->willReturn('stripe_checkout');
        $this->legacyKeyDetector->method('isLegacy')->with('rk_live_abc')->willReturn(false);

        self::assertSame([], $this->createProvider()->getNotifications());
    }

    public function test_it_skips_stripe_payment_methods_without_secret_key(): void
    {
        $paymentMethod = $this->paymentMethodWithGatewayConfig([]);
        $this->paymentMethodRepository->method('findBy')->willReturn([$paymentMethod]);
        $this->gatewayFactoryNameProvider->method('provide')->with($paymentMethod)->willReturn('stripe_checkout');
        $this->legacyKeyDetector->method('isLegacy')->with(null)->willReturn(false);

        self::assertSame([], $this->createProvider()->getNotifications());
    }

    public function test_it_returns_one_notification_per_stripe_payment_method_using_legacy_key(): void
    {
        $legacyOne = $this->paymentMethodWithGatewayConfig(
            ['secret_key' => 'sk_live_abc'],
            id: 11,
            name: 'Stripe Checkout',
        );
        $modern = $this->paymentMethodWithGatewayConfig(
            ['secret_key' => 'rk_live_abc'],
            id: 12,
            name: 'Stripe Modern',
        );
        $legacyTwo = $this->paymentMethodWithGatewayConfig(
            ['secret_key' => 'sk_test_def'],
            id: 13,
            name: 'Stripe Web Elements',
        );

        $this->paymentMethodRepository
            ->method('findBy')
            ->willReturn([$legacyOne, $modern, $legacyTwo])
        ;
        $this->gatewayFactoryNameProvider
            ->method('provide')
            ->willReturnMap([
                [$legacyOne, 'stripe_checkout'],
                [$modern, 'stripe_checkout'],
                [$legacyTwo, 'stripe_web_elements'],
            ])
        ;
        $this->legacyKeyDetector
            ->method('isLegacy')
            ->willReturnMap([
                ['sk_live_abc', true],
                ['rk_live_abc', false],
                ['sk_test_def', true],
            ])
        ;

        $notifications = $this->createProvider()->getNotifications();

        self::assertSame([
            'legacy_stripe_secret_key.11' => [
                'message' => 'flux_se_sylius_stripe_plugin.admin.notification.legacy_secret_key',
                '%payment_method_name%' => 'Stripe Checkout',
                'route' => 'sylius_admin_payment_method_update',
                'route_parameters' => ['id' => 11],
            ],
            'legacy_stripe_secret_key.13' => [
                'message' => 'flux_se_sylius_stripe_plugin.admin.notification.legacy_secret_key',
                '%payment_method_name%' => 'Stripe Web Elements',
                'route' => 'sylius_admin_payment_method_update',
                'route_parameters' => ['id' => 13],
            ],
        ], $notifications);
    }

    private function createProvider(): LegacyStripeKeyNotificationProvider
    {
        return new LegacyStripeKeyNotificationProvider(
            $this->paymentMethodRepository,
            $this->gatewayFactoryNameProvider,
            $this->legacyKeyDetector,
            self::STRIPE_FACTORY_NAMES,
        );
    }

    /** @param array<string, mixed> $config */
    private function paymentMethodWithGatewayConfig(
        array $config,
        int $id = 1,
        string $name = 'Stripe',
    ): PaymentMethodInterface&MockObject {
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getConfig')->willReturn($config);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);
        $paymentMethod->method('getId')->willReturn($id);
        $paymentMethod->method('getName')->willReturn($name);

        return $paymentMethod;
    }
}
