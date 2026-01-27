<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SyliusRefundSupportedGatewaysCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sylius_refund.supported_gateways')) {
            return;
        }

        if (!$container->hasParameter('flux_se.sylius_stripe.factories')) {
            return;
        }

        /** @var string[] $supportedGateways */
        $supportedGateways = $container->getParameter('sylius_refund.supported_gateways');

        /** @var string[] $stripeFactories */
        $stripeFactories = $container->getParameter('flux_se.sylius_stripe.factories');

        $mergedGateways = array_unique(array_merge($supportedGateways, $stripeFactories));

        $container->setParameter('sylius_refund.supported_gateways', $mergedGateways);
    }
}

