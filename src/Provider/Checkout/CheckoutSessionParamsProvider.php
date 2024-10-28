<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\AfterUrlTypeEnum;
use FluxSE\SyliusStripePlugin\Provider\CustomerEmailProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\PaymentMethodTypesProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class CheckoutSessionParamsProvider implements ParamsProviderInterface
{
    public function __construct(
        private CustomerEmailProviderInterface $customerEmailProvider,
        private ModeProviderInterface $modeProvider,
        private LineItemsProviderInterface $lineItemsProvider,
        private PaymentMethodTypesProviderInterface $paymentMethodTypesProvider,
        private MetadataProviderInterface $metadataProvider,
        private AfterUrlProviderInterface $afterUrlProvider,
    ) {
    }

    public function getParams(PaymentRequestInterface $paymentRequest, string $method): ?array
    {
        if (false === $this->supportsAction($paymentRequest, $method)) {
            return null;
        }

        $details = [];

        $customerEmail = $this->customerEmailProvider->getCustomerEmail($paymentRequest);
        if (null !== $customerEmail) {
            $details['customer_email'] = $customerEmail;
        }

        $details['mode'] = $this->modeProvider->getMode($paymentRequest);

        $lineItems = $this->lineItemsProvider->getLineItems($paymentRequest);
        if (null !== $lineItems) {
            $details['line_items'] = $lineItems;
        }

        $paymentMethodTypes = $this->paymentMethodTypesProvider->getPaymentMethodTypes($paymentRequest);
        if ([] !== $paymentMethodTypes) {
            $details['payment_method_types'] = $paymentMethodTypes;
        }

        $details['metadata'] = $this->metadataProvider->getMetadata($paymentRequest);

        foreach ([
                     AfterUrlTypeEnum::SUCCESS,
                     AfterUrlTypeEnum::CANCEL,
                 ] as $case) {
            $details[$case->value] = $this->afterUrlProvider->getUrl($paymentRequest, $case);
        }

        return $details;
    }

    protected function supportsAction(PaymentRequestInterface $paymentRequest, string $method): bool
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
