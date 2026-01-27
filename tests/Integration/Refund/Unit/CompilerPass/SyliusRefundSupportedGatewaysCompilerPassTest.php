<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Integration\Refund\Unit\CompilerPass;

use FluxSE\SyliusStripePlugin\DependencyInjection\CompilerPass\SyliusRefundSupportedGatewaysCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SyliusRefundSupportedGatewaysCompilerPassTest extends TestCase
{
    public function test_it_does_nothing_when_sylius_refund_parameter_is_missing(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('flux_se.sylius_stripe.factories', ['stripe_checkout', 'stripe_web_elements']);

        $compilerPass = new SyliusRefundSupportedGatewaysCompilerPass();
        $compilerPass->process($container);

        self::assertFalse($container->hasParameter('sylius_refund.supported_gateways'));
    }

    public function test_it_does_nothing_when_stripe_factories_parameter_is_missing(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius_refund.supported_gateways', ['offline']);

        $compilerPass = new SyliusRefundSupportedGatewaysCompilerPass();
        $compilerPass->process($container);

        self::assertSame(['offline'], $container->getParameter('sylius_refund.supported_gateways'));
    }

    public function test_it_merges_stripe_factories_with_existing_supported_gateways(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius_refund.supported_gateways', ['offline', 'paypal']);
        $container->setParameter('flux_se.sylius_stripe.factories', ['stripe_checkout', 'stripe_web_elements']);

        $compilerPass = new SyliusRefundSupportedGatewaysCompilerPass();
        $compilerPass->process($container);

        /** @var string[] $supportedGateways */
        $supportedGateways = $container->getParameter('sylius_refund.supported_gateways');

        self::assertContains('offline', $supportedGateways);
        self::assertContains('paypal', $supportedGateways);
        self::assertContains('stripe_checkout', $supportedGateways);
        self::assertContains('stripe_web_elements', $supportedGateways);
    }

    public function test_it_does_not_duplicate_gateways(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius_refund.supported_gateways', ['offline', 'stripe_checkout']);
        $container->setParameter('flux_se.sylius_stripe.factories', ['stripe_checkout', 'stripe_web_elements']);

        $compilerPass = new SyliusRefundSupportedGatewaysCompilerPass();
        $compilerPass->process($container);

        /** @var string[] $supportedGateways */
        $supportedGateways = $container->getParameter('sylius_refund.supported_gateways');

        self::assertCount(3, $supportedGateways);
        self::assertContains('offline', $supportedGateways);
        self::assertContains('stripe_checkout', $supportedGateways);
        self::assertContains('stripe_web_elements', $supportedGateways);
    }

    public function test_it_works_with_empty_existing_gateways(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius_refund.supported_gateways', []);
        $container->setParameter('flux_se.sylius_stripe.factories', ['stripe_checkout', 'stripe_web_elements']);

        $compilerPass = new SyliusRefundSupportedGatewaysCompilerPass();
        $compilerPass->process($container);

        /** @var string[] $supportedGateways */
        $supportedGateways = $container->getParameter('sylius_refund.supported_gateways');

        self::assertCount(2, $supportedGateways);
        self::assertContains('stripe_checkout', $supportedGateways);
        self::assertContains('stripe_web_elements', $supportedGateways);
    }
}

