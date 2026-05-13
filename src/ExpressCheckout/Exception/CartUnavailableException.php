<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Exception;

final class CartUnavailableException extends ExpressCheckoutException
{
    public static function notFound(): self
    {
        return new self('Cart not found.');
    }

    public static function empty(): self
    {
        return new self('Cart is empty.');
    }
}
