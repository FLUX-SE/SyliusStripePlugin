<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\Util\RequestOptions;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class EventOptsProvider implements OptsProviderInterface
{
    public function getOpts(PaymentRequestInterface $paymentRequest): ?RequestOptions
    {
        return null;
    }
}
