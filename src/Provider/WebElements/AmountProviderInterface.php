<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface AmountProviderInterface
{
    public function getAmount(PaymentRequestInterface $paymentRequest): int;
}
