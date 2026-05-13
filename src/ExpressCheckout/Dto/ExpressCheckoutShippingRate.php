<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Dto;

final readonly class ExpressCheckoutShippingRate
{
    public function __construct(
        public string $id,
        public string $displayName,
        public int $amount,
        public ?string $currency,
    ) {
    }

    /**
     * @return array{
     *     id: string,
     *     displayName: string,
     *     amount: int,
     *     currency: string|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'displayName' => $this->displayName,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
