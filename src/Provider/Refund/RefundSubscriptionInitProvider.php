<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund;

use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class RefundSubscriptionInitProvider implements PaymentIntentToRefundProviderInterface
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
        if (Session::MODE_SUBSCRIPTION !== $mode) {
            return null;
        }

        /** @var array{payments?: array{data?: array<int, array{payment?: array{type?: string, payment_intent?: string|array{id?: string}}}>}}|null $invoice */
        $invoice = $paymentRequest->getPayment()->getDetails()['invoice'] ?? null;
        if (null === $invoice) {
            return null;
        }

        $invoicePayments = $invoice['payments']['data'] ?? [];
        foreach ($invoicePayments as $invoicePayment) {
            $payment = $invoicePayment['payment'] ?? null;
            if (null === $payment || 'payment_intent' !== ($payment['type'] ?? null)) {
                continue;
            }

            $paymentIntent = $payment['payment_intent'] ?? null;
            if (is_array($paymentIntent)) {
                return $paymentIntent['id'] ?? null;
            }

            return $paymentIntent;
        }

        return null;
    }
}
