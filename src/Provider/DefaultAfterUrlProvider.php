<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

final readonly class DefaultAfterUrlProvider implements AfterUrlProviderInterface
{
    /**
     * @param array<string, string> $defaultAfterPayUrls
     */
    public function __construct(
        private array $defaultAfterPayUrls,
    ) {
    }

    public function getUrl(PaymentRequestInterface $paymentRequest, string $type): string
    {
        $defaultUrl = $this->defaultAfterPayUrls[$type] ?? null;
        Assert::notNull($defaultUrl, sprintf('No default after url set for "%s".', $type));

        return $defaultUrl;
    }
}
