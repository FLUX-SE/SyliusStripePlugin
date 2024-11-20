<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 * @implements InnerParamsProviderInterface<T>
 */
final readonly class PaymentMethodTypesProvider implements InnerParamsProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        /** @var string[] $paymentMethodTypes */
        $paymentMethodTypes = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig()['payment_method_types'] ?? [];
        if ([] === $paymentMethodTypes) {
            return;
        }

        $params['payment_method_types'] = $paymentMethodTypes;
    }
}
