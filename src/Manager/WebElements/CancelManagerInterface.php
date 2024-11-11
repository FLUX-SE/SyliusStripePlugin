<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\WebElements;

use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface CancelManagerInterface
{
    public function cancel(PaymentRequestInterface $paymentRequest, string $id): PaymentIntent;
}
