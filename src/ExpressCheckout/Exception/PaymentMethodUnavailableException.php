<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Exception;

final class PaymentMethodUnavailableException extends ExpressCheckoutException
{
    public static function notConfigured(): self
    {
        return new self('No Express Checkout payment method is configured for this channel.');
    }

    public static function missingPublishableKey(): self
    {
        return new self('Express Checkout payment method has no publishable key configured.');
    }
}
