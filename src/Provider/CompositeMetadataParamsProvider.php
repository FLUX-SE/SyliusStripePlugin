<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 * @implements InnerParamsProviderInterface<T>
 */
final readonly class CompositeMetadataParamsProvider implements InnerParamsProviderInterface
{
    /**
     * @param InnerParamsProviderInterface<T>[] $innerParamsProviders
     */
    public function __construct(
        private iterable $innerParamsProviders,
    ) {
    }

    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        if (false === isset($params['metadata'])) {
            $params['metadata'] = [];
        }

        foreach ($this->innerParamsProviders as $innerParamsProvider) {
            $innerParamsProvider->provide($paymentRequest, $params['metadata']);
        }
    }
}
