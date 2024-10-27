<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use Stripe\Util\RequestOptions;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class CheckoutSessionOptsProvider implements OptsProviderInterface
{
    public function getOpts(PaymentRequestInterface $paymentRequest): ?RequestOptions
    {
        return null;
    }
}
