<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
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

    public function getParams(PaymentRequestInterface $paymentRequest): ?array
    {
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
                     AfterUrlProviderInterface::SUCCESS_URL,
                     AfterUrlProviderInterface::CANCEL_URL,
                 ] as $case) {
            $details[$case] = $this->afterUrlProvider->getUrl($paymentRequest, $case);
        }

        return $details;
    }
}
