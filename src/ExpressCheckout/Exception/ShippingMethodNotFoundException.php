<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Exception;

final class ShippingMethodNotFoundException extends ExpressCheckoutException
{
    public static function forCode(string $code): self
    {
        return new self(sprintf('Unknown shipping rate "%s".', $code));
    }

    public static function shipmentMissing(): self
    {
        return new self('Cart has no shipment to attach a shipping method to.');
    }
}
