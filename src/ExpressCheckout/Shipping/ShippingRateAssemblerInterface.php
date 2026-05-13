<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutShippingRate;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;

interface ShippingRateAssemblerInterface
{
    /**
     * @param iterable<ShippingMethodInterface> $methods
     *
     * @return list<ExpressCheckoutShippingRate>
     */
    public function assemble(ShipmentInterface $shipment, iterable $methods, ?string $currencyCode): array;
}
