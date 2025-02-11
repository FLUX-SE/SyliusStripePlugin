<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\ProductData;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\ShipmentLineItemProviderInterface;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements ShipmentLineItemProviderInterface<StripeObject>
 */
final class ShipmentNameProvider implements ShipmentLineItemProviderInterface
{
    public function provideFromShipment(
        ShipmentInterface $shipment,
        PaymentRequestInterface $paymentRequest,
        array &$params,
    ): void {
        $shipmentMethod = $shipment->getMethod();
        if (null === $shipmentMethod) {
            return;
        }

        $params['name'] = $shipmentMethod->getName();
    }
}
