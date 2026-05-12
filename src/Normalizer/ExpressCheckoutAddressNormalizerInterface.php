<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Normalizer;

use Sylius\Component\Core\Model\AddressInterface;

interface ExpressCheckoutAddressNormalizerInterface
{
    /** @param array<string, mixed> $payload */
    public function normalizeShipping(array $payload): AddressInterface;

    /** @param array<string, mixed> $payload */
    public function normalizeBilling(array $payload, AddressInterface $shippingFallback): AddressInterface;

    /**
     * Build an Address from a flat address dictionary (line1, line2, city, state, postal_code, country).
     * Used for partial addresses emitted by the Express Checkout Element (e.g. shippingaddresschange).
     *
     * @param array<string, mixed> $address
     */
    public function normalizeAddress(array $address): AddressInterface;
}
