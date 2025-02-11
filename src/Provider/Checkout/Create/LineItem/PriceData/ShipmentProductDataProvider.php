<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\ShipmentLineItemProviderInterface;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements ShipmentLineItemProviderInterface<StripeObject>
 */
final class ShipmentProductDataProvider implements ShipmentLineItemProviderInterface
{
    /**
     * @param ShipmentLineItemProviderInterface<StripeObject>[] $shipmentProductDataProviders
     */
    public function __construct(
        private iterable $shipmentProductDataProviders,
    ) {
    }

    public function provideFromShipment(
        ShipmentInterface $shipment,
        PaymentRequestInterface $paymentRequest,
        array &$params,
    ): void {
        /** @var array<key-of<StripeObject>, mixed> $productData */
        $productData = [];

        foreach ($this->shipmentProductDataProviders as $shipmentProductDataProvider) {
            $shipmentProductDataProvider->provideFromShipment($shipment, $paymentRequest, $productData);
        }

        if ([] === $productData) {
            return;
        }

        if (false === isset($params['product_data'])) {
            $params['product_data'] = [];
        }

        $params['product_data'] += $productData;
    }
}
