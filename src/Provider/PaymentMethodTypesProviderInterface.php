<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface PaymentMethodTypesProviderInterface
{
    /**
     * @return string[]
     */
    public function getPaymentMethodTypes(PaymentRequestInterface $paymentRequest): array;
}
