<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\SecretKey;

interface LegacyKeyDetectorInterface
{
    public function isLegacy(?string $secretKey): bool;
}
