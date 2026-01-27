<?php

declare(strict_types=1);

use Sylius\RefundPlugin\SyliusRefundPlugin;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if (!class_exists(SyliusRefundPlugin::class)) {
        return;
    }

    $container->import('@SyliusRefundPlugin/config/config.yaml');

    $container->extension('sylius_refund', [
        'pdf_generator' => [
            'enabled' => false,
        ],
    ]);
};
