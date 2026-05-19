<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Exception;

final class ChannelUnavailableException extends ExpressCheckoutException
{
    public static function notFound(): self
    {
        return new self('Channel not found.');
    }

    public static function withoutCurrency(): self
    {
        return new self('Channel and cart have no resolvable currency code.');
    }
}
