<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class EventParamsProvider implements ParamsProviderInterface
{
    public function getParams(PaymentRequestInterface $paymentRequest, string $method): ?array
    {
        return null;
    }
}
