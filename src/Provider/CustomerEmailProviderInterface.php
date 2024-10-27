<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface CustomerEmailProviderInterface
{
    public function getCustomerEmail(PaymentRequestInterface $paymentRequest): ?string;
}
