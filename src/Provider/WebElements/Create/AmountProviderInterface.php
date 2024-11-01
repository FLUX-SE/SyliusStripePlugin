<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements\Create;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface AmountProviderInterface
{
    public function getAmount(PaymentRequestInterface $paymentRequest): int;
}
