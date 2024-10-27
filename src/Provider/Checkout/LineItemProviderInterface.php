<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface LineItemProviderInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function getLineItem(
        PaymentRequestInterface $paymentRequest,
        OrderItemInterface $orderItem,
    ): ?array;
}
