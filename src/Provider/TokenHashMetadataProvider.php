<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 * @implements DetailsProviderInterface<T>
 */
final readonly class TokenHashMetadataProvider implements DetailsProviderInterface
{
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        if (false === isset($details['metadata'])) {
            $details['metadata'] = [];
        }

        $details['metadata'][MetadataProviderInterface::DEFAULT_TOKEN_HASH_KEY_NAME] = $paymentRequest->getId();
    }
}
