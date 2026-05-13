<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Dto;

final readonly class ExpressCheckoutConfiguration
{
    /** @param list<string> $allowedCountryCodes */
    public function __construct(
        public string $publishableKey,
        public string $paymentMethodCode,
        public string $currency,
        public int $amount,
        public ?string $country,
        public array $allowedCountryCodes,
        public string $merchantName,
        public bool $shippingRequired = true,
    ) {
    }

    /**
     * @return array{
     *     publishableKey: string,
     *     paymentMethodCode: string,
     *     currency: string,
     *     amount: int,
     *     country: string|null,
     *     shippingRequired: bool,
     *     allowedCountryCodes: list<string>,
     *     merchantName: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'publishableKey' => $this->publishableKey,
            'paymentMethodCode' => $this->paymentMethodCode,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'country' => $this->country,
            'shippingRequired' => $this->shippingRequired,
            'allowedCountryCodes' => $this->allowedCountryCodes,
            'merchantName' => $this->merchantName,
        ];
    }
}
