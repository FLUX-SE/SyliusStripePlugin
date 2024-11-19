<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use Stripe\ApiResource;
use Stripe\LineItem;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface ShipmentLineItemProviderInterface
{
    /**
     * @param array<key-of<LineItem>, mixed> $details
     */
    public function getDetails(
        PaymentRequestInterface $paymentRequest,
        ShipmentInterface $shipment,
        array &$details
    ): void;
}
