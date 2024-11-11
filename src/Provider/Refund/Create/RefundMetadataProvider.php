<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund\Create;

use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class RefundMetadataProvider implements MetadataProviderInterface
{
    public function getMetadata(PaymentRequestInterface $paymentRequest): array
    {
        return [
            MetadataProviderInterface::REFUND_TOKEN_HASH_KEY_NAME => $paymentRequest->getId(),
        ];
    }
}
