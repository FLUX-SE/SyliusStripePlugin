<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\SecretKey;

final readonly class LegacyKeyDetector implements LegacyKeyDetectorInterface
{
    public function isLegacy(?string $secretKey): bool
    {
        return $secretKey !== null && str_starts_with($secretKey, 'sk_');
    }
}
