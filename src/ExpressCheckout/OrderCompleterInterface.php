<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutConfirmation;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ExpressCheckoutException;
use Symfony\Component\HttpFoundation\Request;

interface OrderCompleterInterface
{
    /**
     * Runs the full cart confirmation flow against the payload submitted by the
     * Express Checkout Element: applies customer/addresses/shipping, transitions
     * the order through the sylius_order_checkout graph, creates the Payment +
     * PaymentRequest, dispatches the Capture command, and returns the
     * client_secret + return URL the frontend needs to finalize the wallet.
     *
     * @throws ExpressCheckoutException
     */
    public function complete(Request $request): ExpressCheckoutConfirmation;
}
