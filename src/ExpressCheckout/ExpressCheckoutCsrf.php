<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

final class ExpressCheckoutCsrf
{
    public const TOKEN_ID = 'sylius_stripe_express_checkout';

    public const HEADER_NAME = 'X-CSRF-Token';
}
