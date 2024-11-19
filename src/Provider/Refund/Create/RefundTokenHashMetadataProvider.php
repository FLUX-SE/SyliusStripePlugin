<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund\Create;

use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use Stripe\Refund;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<Refund>
 */
final readonly class RefundTokenHashMetadataProvider implements DetailsProviderInterface
{
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        if(false === isset($details['metadata'])) {
            $details['metadata'] = [];
        }

        $details['metadata'][MetadataProviderInterface::REFUND_TOKEN_HASH_KEY_NAME] = $paymentRequest->getId();
    }
}
