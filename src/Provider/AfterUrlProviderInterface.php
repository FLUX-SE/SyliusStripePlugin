<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface AfterUrlProviderInterface
{
    public function getUrl(PaymentRequestInterface $paymentRequest, AfterUrlTypeEnum $type): string;
}
