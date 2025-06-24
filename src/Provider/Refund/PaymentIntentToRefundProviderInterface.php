<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund;

use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface PaymentIntentToRefundProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest): null|string|PaymentIntent;
}
