<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Payload;

/**
 * Immutable typed accessor over the decoded JSON body sent by Stripe's Express
 * Checkout Element. The Element sends partial payloads on shippingaddresschange
 * and a full payload on confirm — accessors return null when fields are absent
 * or of the wrong shape so callers don't have to repeat is_array/is_string checks.
 */
final readonly class ExpressCheckoutPayload
{
    /** @param array<string, mixed> $payload */
    public function __construct(private array $payload)
    {
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->payload;
    }

    /** @return array<string, mixed>|null */
    public function getAddress(): ?array
    {
        $address = $this->payload['address'] ?? null;

        return is_array($address) ? $address : null;
    }

    public function getEmail(): ?string
    {
        $billingDetails = $this->payload['billingDetails'] ?? null;
        if (!is_array($billingDetails)) {
            return null;
        }

        $email = $billingDetails['email'] ?? null;

        return is_string($email) && '' !== $email ? $email : null;
    }

    public function getShippingRateId(): ?string
    {
        $shippingRate = $this->payload['shippingRate'] ?? null;
        if (is_array($shippingRate)) {
            $id = $shippingRate['id'] ?? null;
            if (is_string($id) && '' !== $id) {
                return $id;
            }
        }

        // shippingaddresschange sends a flat "shippingRateId" string instead of a nested object.
        $flat = $this->payload['shippingRateId'] ?? null;

        return is_string($flat) && '' !== $flat ? $flat : null;
    }
}
