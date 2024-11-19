<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 * @implements DetailsProviderInterface<T>
 */
final readonly class PaymentMethodTypesProvider implements DetailsProviderInterface
{
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        /** @var string[] $types */
        $paymentMethodTypes = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig()['payment_method_types'] ?? [];
        if ([] === $paymentMethodTypes) {
            return;
        }

        $details['payment_method_types'] = $paymentMethodTypes;
    }
}
