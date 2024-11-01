<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use Stripe\Checkout\Session;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class ModeProvider implements ModeProviderInterface
{
    public function getMode(PaymentRequestInterface $paymentRequest): string
    {
        return Session::MODE_PAYMENT;
    }
}
