<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface LineItemsProviderInterface
{
    /**
     * @return array<array-key, mixed>|null
     */
    public function getLineItems(PaymentRequestInterface $paymentRequest): ?array;
}
