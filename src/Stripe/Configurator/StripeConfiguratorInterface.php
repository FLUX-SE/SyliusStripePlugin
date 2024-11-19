<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Configurator;

interface StripeConfiguratorInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function configure(array $config): void;

    public function unConfigure(): void;
}
