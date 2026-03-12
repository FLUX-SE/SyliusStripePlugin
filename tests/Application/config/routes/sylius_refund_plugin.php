<?php

declare(strict_types=1);

use Sylius\RefundPlugin\SyliusRefundPlugin;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    if (!class_exists(SyliusRefundPlugin::class)) {
        return;
    }

    $routes->import('@SyliusRefundPlugin/config/routes.yaml');
};
