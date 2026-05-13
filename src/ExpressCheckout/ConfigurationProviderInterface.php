<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutConfiguration;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ExpressCheckoutException;

interface ConfigurationProviderInterface
{
    /**
     * Builds the boot configuration for the Express Checkout Element on the cart page.
     *
     * @throws ExpressCheckoutException when the wallet should silently disappear
     *                                  (no cart/channel/payment method available)
     */
    public function provide(): ExpressCheckoutConfiguration;
}
