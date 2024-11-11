<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund\Create;

use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

final readonly class RefundParamsProvider implements ParamsProviderInterface
{
    public function __construct(
        private MetadataProviderInterface $metadataProvider,
    ) {
    }

    public function getParams(PaymentRequestInterface $paymentRequest): ?array
    {
        $params = $paymentRequest->getPayload();
        Assert::isArray($params, 'The payment request must be an array.');
        Assert::keyExists($params, 'payment_intent');

        if (null === $params['amount']) {
            unset($params['amount']);
        }

        $params['metadata'] = $this->metadataProvider->getMetadata($paymentRequest);

        return $params;
    }
}
