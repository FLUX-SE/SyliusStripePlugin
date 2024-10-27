<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Bundle\CoreBundle\OrderPay\Provider\AfterPayUrlProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AfterUrlProvider implements AfterUrlProviderInterface
{
    public function __construct(
        private AfterPayUrlProviderInterface $afterPayUrlProvider,
    ) {}

    public function getUrl(PaymentRequestInterface $paymentRequest, AfterUrlTypeEnum $type): string
    {
        return $this->afterPayUrlProvider->getUrl($paymentRequest, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
