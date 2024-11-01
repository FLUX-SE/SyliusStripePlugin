<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

final readonly class AfterUrlProvider implements AfterUrlProviderInterface
{
    public function __construct(
        private AfterUrlProviderInterface $defaultAfterPayUrlProvider,
    ) {
    }

    public function getUrl(PaymentRequestInterface $paymentRequest, string $type): string
    {
        $payload = $paymentRequest->getPayload();
        Assert::isArray($payload, 'The payment request payload must be an array.');

        return $payload[$type] ?? $this->defaultAfterPayUrlProvider->getUrl($paymentRequest, $type);
    }
}
