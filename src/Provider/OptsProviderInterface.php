<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\Util\RequestOptions;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface OptsProviderInterface
{
    public function getOpts(PaymentRequestInterface $paymentRequest): ?RequestOptions;
}
