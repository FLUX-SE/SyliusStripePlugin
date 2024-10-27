<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Configurator;

interface StripeConfiguratorInterface
{
    public function configure(array $config): void;
}
