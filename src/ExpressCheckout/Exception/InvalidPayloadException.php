<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Exception;

final class InvalidPayloadException extends ExpressCheckoutException
{
    public static function missingAddress(): self
    {
        return new self('Missing "address" in request body.');
    }

    public static function missingEmail(): self
    {
        return new self('Missing customer email in payload.');
    }

    public static function missingShippingRateId(): self
    {
        return new self('Missing shipping rate id in payload.');
    }

    public static function invalidShippingAddress(string $reason): self
    {
        return new self($reason);
    }

    public static function invalidBillingAddress(string $reason): self
    {
        return new self($reason);
    }
}
