<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class EventParamsProvider implements ParamsProviderInterface
{
    public function getParams(PaymentRequestInterface $paymentRequest): ?array
    {
        return null;
    }
}
