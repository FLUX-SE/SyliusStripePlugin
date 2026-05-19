<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Dto;

final readonly class ExpressCheckoutLineItem
{
    public function __construct(
        public string $name,
        public int $amount,
    ) {
    }

    /** @return array{name: string, amount: int} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'amount' => $this->amount,
        ];
    }
}
