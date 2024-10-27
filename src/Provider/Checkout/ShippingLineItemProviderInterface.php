<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface ShippingLineItemProviderInterface
{
    /**
     * @return array<array-key, mixed>|null
     */
    public function getLineItem(PaymentRequestInterface $paymentRequest, ShipmentInterface $shipment): ?array;
}
