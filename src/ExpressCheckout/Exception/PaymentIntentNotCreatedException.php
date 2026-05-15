<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Exception;

final class PaymentIntentNotCreatedException extends ExpressCheckoutException
{
    public static function missingClientSecret(): self
    {
        return new self('Stripe did not return a client_secret for the PaymentIntent.');
    }

    public static function cartHasNoCurrency(): self
    {
        return new self('Cart must have a currency code set before creating an Express Checkout payment.');
    }
}
