<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\DependencyInjection\CompilerPass;

use FluxSE\SyliusStripePlugin\Twig\Component\AdminPaymentMethod\FormComponent;
use FluxSE\SyliusStripePlugin\Twig\Component\WebElements\SummaryPaymentComponent;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class LiveTwigComponentCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('sylius_admin.twig.component.payment_method.form');
        if (!$definition->hasTag('sylius.twig_component')) {
            return;
        }

        $tagConfig = $definition->getTag('sylius.twig_component');

        $definition
            ->setClass(FormComponent::class)
            ->addArgument(new Reference('sylius.custom_factory.payment_method'))
            ->addTag('sylius.live_component.admin', $tagConfig[0])
            ->clearTag('sylius.twig_component')
        ;

        $summaryComponentDefinition = new Definition(SummaryPaymentComponent::class);
        $summaryComponentDefinition
            ->setArguments([
                new Reference('request_stack'),
                new Reference('sylius.repository.payment_request'),
                new Reference('monolog.logger'),
            ])
            ->addTag('twig.component', [
                'key' => 'sylius_shop:order_pay:web_elements:summary',
                'template' => '@SyliusShop/cart/index/content/form/sections/general/summary.html.twig'
            ]);

        $container->setDefinition('sylius_live_component.payment_summary', $summaryComponentDefinition);
    }
}
