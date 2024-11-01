<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('flux_se_sylius_stripe');
        /** @var ArrayNodeDefinition $arrayNodeDefinition */
        $arrayNodeDefinition = $treeBuilder->getRootNode();
        $this->addGlobalSection($arrayNodeDefinition);

        return $treeBuilder;
    }

    protected function addGlobalSection(ArrayNodeDefinition $node): void
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('line_item_image')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('imagine_filter')
                            ->defaultNull()
                            ->info('This is the Imagine filter used to get the image displayed on Stripe Checkout Session (default: the filter uses into `@ShopBundle/templates/product/show/page/info/overview/images.html.twig`)')
                        ->end()
                        ->scalarNode('fallback_image')
                            ->defaultValue('https://placehold.co/300')
                            ->info('Fallback image used when no image is set on a product and also when you test this plugin from a private web server (ex: from localhost)')
                        ->end()
                        ->scalarNode('localhost_pattern')
                            ->defaultValue('#://(localhost|127.0.0.1|[^:/]+\.wip|[^:/]+\.local)[:/]#')
                            ->info('Stripe does not display Localhost images because it caches them on a CDN, this preg_match() pattern will allow the line item image provider to test it if the image is from a localhost network or not.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
