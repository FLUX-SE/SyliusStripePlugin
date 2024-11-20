<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\ShipmentLineItemProviderInterface;
use Stripe\LineItem;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements ShipmentLineItemProviderInterface<LineItem>
 */
final class ShipmentLineItemProvider implements ShipmentLineItemProviderInterface
{
    /**
     * @param ShipmentLineItemProviderInterface<LineItem>[] $shipmentLineItemProviders
     */
    public function __construct(
        private iterable $shipmentLineItemProviders,
    ) {
    }

    public function provideFromShipment(
        ShipmentInterface $shipment,
        PaymentRequestInterface $paymentRequest,
        array &$params
    ): void {
        /** @var array<key-of<LineItem>, mixed> $lineItem */
        $lineItem = [];

        foreach ($this->shipmentLineItemProviders as $shipmentLineItemProvider) {
            $shipmentLineItemProvider->provideFromShipment($shipment, $paymentRequest, $lineItem);
        }

        if ([] === $lineItem) {
            return;
        }

        $params[] = $lineItem;
    }
}
