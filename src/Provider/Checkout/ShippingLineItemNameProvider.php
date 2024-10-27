<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class ShippingLineItemNameProvider implements ShippingLineItemNameProviderInterface
{
    public function getItemName(PaymentRequestInterface $paymentRequest, ShipmentInterface $shipment): ?string
    {
        $shipmentMethod = $shipment->getMethod();
        if (null === $shipmentMethod) {
            return null;
        }

        return $shipmentMethod->getName();
    }
}
