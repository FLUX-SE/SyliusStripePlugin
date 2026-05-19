<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Dto;

final readonly class ExpressCheckoutShippingOptions
{
    /**
     * @param list<ExpressCheckoutShippingRate> $shippingRates
     * @param list<ExpressCheckoutLineItem> $lineItems
     */
    public function __construct(
        public array $shippingRates,
        public array $lineItems,
    ) {
    }

    /**
     * @return array{
     *     shippingRates: list<array{id: string, displayName: string, amount: int, currency: string|null}>,
     *     lineItems: list<array{name: string, amount: int}>,
     * }
     */
    public function toArray(): array
    {
        return [
            'shippingRates' => array_map(static fn (ExpressCheckoutShippingRate $rate): array => $rate->toArray(), $this->shippingRates),
            'lineItems' => array_map(static fn (ExpressCheckoutLineItem $item): array => $item->toArray(), $this->lineItems),
        ];
    }
}
