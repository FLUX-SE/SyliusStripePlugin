<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutShippingRate;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Calculator\DelegatingCalculatorInterface;

final readonly class ShippingRateAssembler implements ShippingRateAssemblerInterface
{
    public function __construct(
        private DelegatingCalculatorInterface $shippingCalculator,
    ) {
    }

    public function assemble(ShipmentInterface $shipment, iterable $methods, ?string $currencyCode): array
    {
        $currency = null !== $currencyCode ? strtolower($currencyCode) : null;

        $rates = [];
        foreach ($methods as $method) {
            $shipment->setMethod($method);
            $rates[] = new ExpressCheckoutShippingRate(
                id: (string) $method->getCode(),
                displayName: $method->getName() ?? (string) $method->getCode(),
                amount: $this->shippingCalculator->calculate($shipment),
                currency: $currency,
            );
        }

        return $rates;
    }
}
