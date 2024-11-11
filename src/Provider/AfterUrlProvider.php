<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class AfterUrlProvider implements AfterUrlProviderInterface
{
    public function __construct(
        private AfterUrlProviderInterface $defaultAfterPayUrlProvider,
    ) {
    }

    public function getUrl(PaymentRequestInterface $paymentRequest, string $type): string
    {
        /** @var string[] $responseData */
        $responseData = $paymentRequest->getResponseData();

        return $responseData[$type] ?? $this->defaultAfterPayUrlProvider->getUrl($paymentRequest, $type);
    }
}
