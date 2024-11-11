<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class PaymentMetadataProvider implements MetadataProviderInterface
{
    public function getMetadata(PaymentRequestInterface $paymentRequest): array
    {
        return [
            MetadataProviderInterface::DEFAULT_TOKEN_HASH_KEY_NAME => $paymentRequest->getId(),
        ];
    }
}
