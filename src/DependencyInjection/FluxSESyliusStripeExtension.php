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
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $container->setParameter(
            'flux_se.sylius_stripe.line_item_image.imagine_filter',
            $config['line_item_image']['imagine_filter'],
        );
        $container->setParameter(
            'flux_se.sylius_stripe.line_item_image.fallback_image',
            $config['line_item_image']['fallback_image'],
        );
        $container->setParameter(
            'flux_se.sylius_stripe.line_item_image.localhost_pattern',
            $config['line_item_image']['localhost_pattern'],
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $loader->load('services.yaml');

        if ($container->hasParameter('kernel.bundles')) {
            /** @var string[] $bundles */
            $bundles = $container->getParameter('kernel.bundles');
            if (array_key_exists('SyliusShopBundle', $bundles)) {
                $loader->load('services/integrations/sylius_shop.yaml');
            }
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);
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
}
