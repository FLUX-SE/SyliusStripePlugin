<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Transition\Checkout;

interface SessionModeTransitionProviderInterface extends SessionTransitionProviderInterface
{
    public static function getSupportedMode(): string;
}
