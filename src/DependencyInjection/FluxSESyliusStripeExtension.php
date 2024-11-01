<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class FluxSESyliusStripeExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration([], $container);
        assert(null !== $configuration, 'Configuration cannot be null.');

        $configs = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'flux_se.sylius_stripe.line_item_image.imagine_filter',
            $configs['line_item_image']['imagine_filter'],
        );
        $container->setParameter(
            'flux_se.sylius_stripe.line_item_image.fallback_image',
            $configs['line_item_image']['fallback_image'],
        );
        $container->setParameter(
            'flux_se.sylius_stripe.line_item_image.localhost_pattern',
            $configs['line_item_image']['localhost_pattern'],
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);

        $this->prependSyliusShop($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'DoctrineMigrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@FluxSESyliusStripePlugin/migrations';
    }

    /**
     * @return string[]
     */
    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }

    private function prependSyliusShop(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config/integrations'));

        if ($container->hasExtension('sylius_shop')) {
            $loader->load('sylius_shop.yaml');
        }
    }
}
