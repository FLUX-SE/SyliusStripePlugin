<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements;

use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\PaymentMethodTypesProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class PaymentIntentParamsProvider implements ParamsProviderInterface
{
    public function __construct(
        private AmountProviderInterface $amountProvider,
        private CurrencyProviderInterface $currencyProvider,
        private PaymentMethodTypesProviderInterface $paymentMethodTypesProvider,
        private MetadataProviderInterface $metadataProvider,
    ) {
    }

    public function getParams(PaymentRequestInterface $paymentRequest, string $method): ?array
    {
        if (false === $this->supportsAction($paymentRequest, $method)) {
            return null;
        }

        $details = [
            'amount' => $this->amountProvider->getAmount($paymentRequest),
            'currency' => $this->currencyProvider->getCurrency($paymentRequest),
        ];

        $paymentMethodTypes = $this->paymentMethodTypesProvider->getPaymentMethodTypes($paymentRequest);
        if ([] !== $paymentMethodTypes) {
            $details['payment_method_types'] = $paymentMethodTypes;
        }

        $details['metadata'] = $this->metadataProvider->getMetadata($paymentRequest);

        return $details;
    }

    private function supportsAction(PaymentRequestInterface $paymentRequest, string $method): bool
    {
        return
            'create' === $method &&
            in_array(
                $paymentRequest->getAction(),
                [
                    PaymentRequestInterface::ACTION_CAPTURE,
                    PaymentRequestInterface::ACTION_AUTHORIZE,
                ],
                true
            )
        ;
    }
}
