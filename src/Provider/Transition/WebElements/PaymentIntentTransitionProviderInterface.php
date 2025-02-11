<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Transition\WebElements;

use Stripe\PaymentIntent;

interface PaymentIntentTransitionProviderInterface
{
    public function isAuthorize(PaymentIntent $paymentIntent): bool;

    public function isComplete(PaymentIntent $paymentIntent): bool;

    public function isFail(PaymentIntent $paymentIntent): bool;

    public function isProcess(PaymentIntent $paymentIntent): bool;

    public function isCancel(PaymentIntent $paymentIntent): bool;

    public function isRefund(PaymentIntent $paymentIntent): bool;
}
