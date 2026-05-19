<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutShippingOptions;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ExpressCheckoutException;
use Symfony\Component\HttpFoundation\Request;

interface ShippingOptionsCalculatorInterface
{
    /**
     * Applies the partial shipping address from the wallet popup to the cart, recalculates
     * totals, and returns the shipping rates + line items rendered next to the wallet's
     * Pay button.
     *
     * @throws ExpressCheckoutException
     */
    public function calculate(Request $request): ExpressCheckoutShippingOptions;
}
