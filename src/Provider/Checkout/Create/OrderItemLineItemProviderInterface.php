<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use Stripe\ApiResource;
use Stripe\LineItem;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface OrderItemLineItemProviderInterface
{
    /**
     * @param array<key-of<LineItem>, mixed> $details
     */
    public function getDetails(
        PaymentRequestInterface $paymentRequest,
        OrderItemInterface $orderItem,
        array &$details
    ): void;
}
