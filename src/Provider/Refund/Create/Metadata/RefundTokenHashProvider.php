<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund\Create\Metadata;

use FluxSE\SyliusStripePlugin\Provider\InnerParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use Stripe\Refund;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements InnerParamsProviderInterface<Refund>
 */
final readonly class RefundTokenHashProvider implements InnerParamsProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        $params[MetadataProviderInterface::REFUND_TOKEN_HASH_KEY_NAME] = $paymentRequest->getId();
    }
}
