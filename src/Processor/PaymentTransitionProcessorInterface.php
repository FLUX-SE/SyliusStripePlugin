<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface PaymentTransitionProcessorInterface
{
    public function process(PaymentRequestInterface $paymentRequest): void;
}
