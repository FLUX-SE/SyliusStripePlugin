<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Payment;

use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface CapturePaymentRequestDispatcherInterface
{
    /**
     * Creates a PaymentRequest tied to the given Payment + PaymentMethod, persists it,
     * dispatches the Web Elements Capture command, flushes, and returns the persisted
     * PaymentRequest (with response_data populated by the command handler).
     */
    public function dispatch(PaymentInterface $payment, PaymentMethodInterface $paymentMethod): PaymentRequestInterface;
}
