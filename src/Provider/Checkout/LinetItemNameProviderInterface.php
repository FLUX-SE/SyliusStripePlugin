<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface LinetItemNameProviderInterface
{
    public function getItemName(PaymentRequestInterface $paymentRequest, OrderItemInterface $orderItem): ?string;
}
