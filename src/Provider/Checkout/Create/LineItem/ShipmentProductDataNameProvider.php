<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\ShipmentLineItemProviderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class ShipmentProductDataNameProvider implements ShipmentLineItemProviderInterface
{
    public function getDetails(
        PaymentRequestInterface $paymentRequest,
        ShipmentInterface $shipment,
        array &$details
    ): void {
        $shipmentMethod = $shipment->getMethod();
        if (null === $shipmentMethod) {
            return;
        }

        if (false === isset($details['price_data'])) {
            $details['price_data'] = [];
        }

        if (false === isset($details['price_data']['product_data'])) {
            $details['price_data']['product_data'] = [];
        }

        $details['price_data']['product_data']['name'] = $shipmentMethod->getName();
    }
}
