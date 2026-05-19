<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 * @implements InnerParamsProviderInterface<T>
 *
 * @deprecated since 1.1, will be removed in 2.0. The static `payment_method_types` gateway configuration is being
 *             replaced by Stripe's automatic payment methods (configured from the Stripe Dashboard).
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

        trigger_deprecation(
            'flux-se/sylius-stripe-plugin',
            '1.1',
            'Configuring "payment_method_types" in gateway configuration is deprecated and will be removed in 2.0. Manage active payment methods in your Stripe Dashboard instead.',
        );

        $params['payment_method_types'] = $paymentMethodTypes;
    }
}
