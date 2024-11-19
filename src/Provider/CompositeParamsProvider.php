<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Stripe\Checkout\Session;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 * @implements ParamsProviderInterface<T>
 */
final readonly class CompositeParamsProvider implements ParamsProviderInterface
{
    /**
     * @param DetailsProviderInterface<T>[] $detailsProviders
     */
    public function __construct(
        private iterable $detailsProviders,
    ) {
    }

    /**
     * @return array<key-of<Session>, mixed>|null
     */
    public function getParams(PaymentRequestInterface $paymentRequest): ?array
    {
        /** @var array<key-of<Session>, mixed> $details */
        $details = [];

        foreach ($this->detailsProviders as $checkoutSessionProvider) {
            $checkoutSessionProvider->getDetails($paymentRequest, $details);
        }

        return $details;
    }
}
