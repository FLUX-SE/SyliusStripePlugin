<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\ShipmentLineItemProviderInterface;
use Stripe\LineItem;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements ShipmentLineItemProviderInterface<LineItem>
 */
final class ShipmentPriceDataProvider implements ShipmentLineItemProviderInterface
{
    /**
     * @param ShipmentLineItemProviderInterface<StripeObject>[] $shipmentPriceDataProviders
     */
    public function __construct(
        private iterable $shipmentPriceDataProviders,
    ) {
    }

    public function provideFromShipment(
        ShipmentInterface $shipment,
        PaymentRequestInterface $paymentRequest,
        array &$params
    ): void {
        /** @var array<key-of<StripeObject>, mixed> $priceData */
        $priceData = [];

        foreach ($this->shipmentPriceDataProviders as $shipmentPriceDataProvider) {
            $shipmentPriceDataProvider->provideFromShipment($shipment, $paymentRequest, $priceData);
        }

        if ([] === $priceData) {
            return;
        }

        if (false === isset($params['price_data'])) {
            $params['price_data'] = [];
        }

        $params['price_data'] += $priceData;
    }
}
