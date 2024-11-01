<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Bundle\CoreBundle\OrderPay\Provider\UrlProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SyliusShopAfterUrlProvider implements AfterUrlProviderInterface
{
    public function __construct(
        private UrlProviderInterface $afterPayUrlProvider,
    ) {
    }

    public function getUrl(PaymentRequestInterface $paymentRequest, string $type): string
    {
        return $this->afterPayUrlProvider->getUrl($paymentRequest, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
