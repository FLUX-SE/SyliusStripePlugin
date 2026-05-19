<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Dto;

final readonly class ExpressCheckoutConfirmation
{
    public function __construct(
        public string $clientSecret,
        public string $returnUrl,
    ) {
    }

    /** @return array{clientSecret: string, returnUrl: string} */
    public function toArray(): array
    {
        return [
            'clientSecret' => $this->clientSecret,
            'returnUrl' => $this->returnUrl,
        ];
    }
}
