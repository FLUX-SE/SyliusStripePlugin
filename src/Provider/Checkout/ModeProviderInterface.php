<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface ModeProviderInterface
{
    public function getMode(PaymentRequestInterface $paymentRequest): string;
}
