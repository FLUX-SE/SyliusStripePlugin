<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Normalizer;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final readonly class ExpressCheckoutAddressNormalizer implements ExpressCheckoutAddressNormalizerInterface
{
    private const PLACEHOLDER = '?';

    public function __construct(
        private FactoryInterface $addressFactory,
    ) {
    }

    public function normalizeShipping(array $payload): AddressInterface
    {
        $shippingAddress = $payload['shippingAddress'] ?? null;
        if (!is_array($shippingAddress)) {
            throw new \InvalidArgumentException('Missing or invalid "shippingAddress" in payload.');
        }

        return $this->fromShippingAddress($shippingAddress);
    }

    public function normalizeAddress(array $address): AddressInterface
    {
        $result = $this->createAddress();
        $this->applyAddressFields($result, $address);

        return $result;
    }

    /**
     * Stripe's Express Checkout Element normalizes the wallet-specific shipping payload
     * (Apple Pay `shippingContact`, Google Pay's `shippingAddress`, Link, etc.) into a
     * single shape: `{name, address: {line1, line2, city, state, postal_code, country}, phone}`.
     *
     * @param array<string, mixed> $shippingAddress
     */
    private function fromShippingAddress(array $shippingAddress): AddressInterface
    {
        $address = $this->createAddress();

        $this->applyFullName($address, $this->stringOrNull($shippingAddress['name'] ?? null));

        $addressFields = $shippingAddress['address'] ?? null;
        if (is_array($addressFields)) {
            $this->applyAddressFields($address, $addressFields);
        }

        $address->setPhoneNumber($this->stringOrNull($shippingAddress['phone'] ?? null));

        return $address;
    }

    /**
     * Merge — only overwrite fields that are actually present in the payload, so that
     * placeholders set by {@see createAddress()} survive when the Express Checkout Element
     * sends a partial address (no `line1`, no `name`) on `shippingaddresschange`.
     *
     * @param array<string, mixed> $fields
     */
    private function applyAddressFields(AddressInterface $address, array $fields): void
    {
        $line1 = $this->stringOrNull($fields['line1'] ?? null);
        $line2 = $this->stringOrNull($fields['line2'] ?? null);
        if (null !== $line1 || null !== $line2) {
            $street = trim(($line1 ?? '') . (null !== $line2 ? ' ' . $line2 : ''));
            if ('' !== $street) {
                $address->setStreet($street);
            }
        }

        $city = $this->stringOrNull($fields['city'] ?? null);
        if (null !== $city) {
            $address->setCity($city);
        }

        $postcode = $this->stringOrNull($fields['postal_code'] ?? null);
        if (null !== $postcode) {
            $address->setPostcode($postcode);
        }

        $state = $this->stringOrNull($fields['state'] ?? null);
        if (null !== $state) {
            $address->setProvinceName($state);
        }

        $country = $this->stringOrNull($fields['country'] ?? null);
        if (null !== $country) {
            $address->setCountryCode($country);
        }
    }

    private function applyFullName(AddressInterface $address, ?string $fullName): void
    {
        if (null === $fullName) {
            return;
        }

        $parts = explode(' ', trim($fullName), 2);
        $address->setFirstName($parts[0] ?? null);
        $address->setLastName($parts[1] ?? null);
    }

    private function createAddress(): AddressInterface
    {
        $address = $this->addressFactory->createNew();
        if (!$address instanceof AddressInterface) {
            throw new \UnexpectedValueException(sprintf('Address factory must produce instances of "%s".', AddressInterface::class));
        }

        // Sylius's sylius_address table declares first_name / last_name / street as NOT NULL.
        // The Express Checkout Element's `shippingaddresschange` event omits the customer
        // name and line1 (Stripe privacy rule) — without placeholders the order processor
        // would fail with a Doctrine integrity-constraint violation when the cart is
        // flushed during shipping-rate preview. `applyFullName` / `applyAddressFields`
        // overwrite these placeholders as soon as a full payload (confirm event) arrives.
        $address->setFirstName(self::PLACEHOLDER);
        $address->setLastName(self::PLACEHOLDER);
        $address->setStreet(self::PLACEHOLDER);
        $address->setCity(self::PLACEHOLDER);
        $address->setPostcode(self::PLACEHOLDER);

        return $address;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && '' !== $value ? $value : null;
    }
}
