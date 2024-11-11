<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\WebElements;

use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface CaptureManagerInterface
{
    public function capture(PaymentRequestInterface $paymentRequest, string $id): PaymentIntent;
}
