<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('flux_s_e_sylius_stripe');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
