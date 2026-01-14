<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund;

use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class RefundPaymentProvider implements PaymentIntentToRefundProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest): null|string|PaymentIntent
    {
        /** @var string|null $object */
        $object = $paymentRequest->getPayment()->getDetails()['object'] ?? null;
        if (Session::OBJECT_NAME !== $object) {
            return null;
        }

        /** @var string|null $mode */
        $mode = $paymentRequest->getPayment()->getDetails()['mode'] ?? null;
        if (Session::MODE_PAYMENT !== $mode) {
            return null;
        }

        /** @var string|array{id?: string}|null $paymentIntent */
        $paymentIntent = $paymentRequest->getPayment()->getDetails()['payment_intent'] ?? null;

        if (is_array($paymentIntent)) {
            return $paymentIntent['id'] ?? null;
        }

        return $paymentIntent;
    }
}
