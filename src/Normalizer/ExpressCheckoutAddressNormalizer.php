<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Normalizer;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final readonly class ExpressCheckoutAddressNormalizer implements ExpressCheckoutAddressNormalizerInterface
{
    public function __construct(
        private FactoryInterface $addressFactory,
    ) {
    }

    public function normalizeShipping(array $payload): AddressInterface
    {
        $type = $payload['expressPaymentType'] ?? null;
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Missing or invalid "expressPaymentType" in payload.');
        }

        return match ($type) {
            self::TYPE_GOOGLE_PAY => $this->fromGooglePayShippingAddress(
                is_array($payload['shippingAddress'] ?? null) ? $payload['shippingAddress'] : [],
            ),
            self::TYPE_APPLE_PAY => throw new \LogicException('Apple Pay address normalization is not yet implemented.'),
            default => throw new \InvalidArgumentException(sprintf('Unsupported express payment type "%s".', $type)),
        };
    }

    public function normalizeBilling(array $payload, AddressInterface $shippingFallback): AddressInterface
    {
        $billingDetails = $payload['billingDetails'] ?? null;
        if (!is_array($billingDetails)) {
            return clone $shippingFallback;
        }

        $address = $billingDetails['address'] ?? null;
        if (!is_array($address) || !$this->hasCompleteAddress($address)) {
            return clone $shippingFallback;
        }

        $billing = $this->createAddress();
        $this->applyFullName($billing, $this->stringOrNull($billingDetails['name'] ?? null));
        $this->applyAddressFields($billing, $address);
        $billing->setPhoneNumber($this->stringOrNull($billingDetails['phone'] ?? null));

        return $billing;
    }

    /** @param array<string, mixed> $shippingAddress */
    private function fromGooglePayShippingAddress(array $shippingAddress): AddressInterface
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

    /** @param array<string, mixed> $fields */
    private function applyAddressFields(AddressInterface $address, array $fields): void
    {
        $line1 = $this->stringOrNull($fields['line1'] ?? null);
        $line2 = $this->stringOrNull($fields['line2'] ?? null);
        $street = trim(($line1 ?? '') . (null !== $line2 ? ' ' . $line2 : ''));

        $address->setStreet('' !== $street ? $street : null);
        $address->setCity($this->stringOrNull($fields['city'] ?? null));
        $address->setPostcode($this->stringOrNull($fields['postal_code'] ?? null));
        $address->setProvinceName($this->stringOrNull($fields['state'] ?? null));
        $address->setCountryCode($this->stringOrNull($fields['country'] ?? null));
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

    /** @param array<string, mixed> $address */
    private function hasCompleteAddress(array $address): bool
    {
        return null !== $this->stringOrNull($address['line1'] ?? null) &&
            null !== $this->stringOrNull($address['country'] ?? null);
    }

    private function createAddress(): AddressInterface
    {
        $address = $this->addressFactory->createNew();
        if (!$address instanceof AddressInterface) {
            throw new \UnexpectedValueException(sprintf('Address factory must produce instances of "%s".', AddressInterface::class));
        }

        return $address;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && '' !== $value ? $value : null;
    }
}
