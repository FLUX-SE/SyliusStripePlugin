<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Normalizer;

use Sylius\Component\Core\Model\AddressInterface;

interface ExpressCheckoutAddressNormalizerInterface
{
    public const TYPE_GOOGLE_PAY = 'google_pay';

    public const TYPE_APPLE_PAY = 'apple_pay';

    /** @param array<string, mixed> $payload */
    public function normalizeShipping(array $payload): AddressInterface;

    /** @param array<string, mixed> $payload */
    public function normalizeBilling(array $payload, AddressInterface $shippingFallback): AddressInterface;
}
