<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface ParamsProviderInterface
{
    /**
     * @return array<array-key, mixed>|null
     */
    public function getParams(PaymentRequestInterface $paymentRequest): ?array;
}
