<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface ShippingLineItemNameProviderInterface
{
    public function getItemName(PaymentRequestInterface $paymentRequest, ShipmentInterface $shipment): ?string;
}
