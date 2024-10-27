<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements;

use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use Stripe\Util\RequestOptions;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class PaymentIntentOptsProvider implements OptsProviderInterface
{
    public function getOpts(PaymentRequestInterface $paymentRequest): ?RequestOptions
    {
        return null;
    }
}
