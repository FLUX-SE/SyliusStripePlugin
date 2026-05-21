<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Twig\Extension;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LegacyStripeKeyExtension extends AbstractExtension
{
    public function __construct(
        private readonly LegacyKeyDetectorInterface $legacyKeyDetector,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'sylius_stripe_is_legacy_secret_key',
                $this->legacyKeyDetector->isLegacy(...),
            ),
        ];
    }
}
