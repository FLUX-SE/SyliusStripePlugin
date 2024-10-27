<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class PaymentMethodTypesProvider implements PaymentMethodTypesProviderInterface
{
    public function getPaymentMethodTypes(PaymentRequestInterface $paymentRequest): array
    {
        /** @var string[] $types */
        $types = $paymentRequest->getMethod()->getGatewayConfig()?->getConfig()['payment_method_types'] ?? [];

        return $types;
    }
}
