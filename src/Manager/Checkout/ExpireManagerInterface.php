<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Checkout;

use Stripe\Checkout\Session;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface ExpireManagerInterface
{
    public function expire(PaymentRequestInterface $paymentRequest, string $id): Session;
}
