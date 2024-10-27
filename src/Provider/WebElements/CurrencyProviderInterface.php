<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface CurrencyProviderInterface
{
    public function getCurrency(PaymentRequestInterface $paymentRequest): string;
}
