<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Transition\WebElements;

use FluxSE\SyliusStripePlugin\Provider\ExpandProvider;
use Stripe\Charge;
use Stripe\PaymentIntent;

final class PaymentIntentTransitionProvider implements PaymentIntentTransitionProviderInterface
{
    public function isAuthorize(PaymentIntent $paymentIntent): bool
    {
        return PaymentIntent::STATUS_REQUIRES_CAPTURE === $paymentIntent->status;
    }

    public function isComplete(PaymentIntent $paymentIntent): bool
    {
        return PaymentIntent::STATUS_SUCCEEDED === $paymentIntent->status;
    }

    public function isFail(PaymentIntent $paymentIntent): bool
    {
        return false;
    }

    public function isProcess(PaymentIntent $paymentIntent): bool
    {
        return PaymentIntent::STATUS_PROCESSING === $paymentIntent->status;
    }

    public function isCancel(PaymentIntent $paymentIntent): bool
    {
        return PaymentIntent::STATUS_CANCELED === $paymentIntent->status || $this->isSpecialCancelStatus($paymentIntent);
    }

    public function isRefund(PaymentIntent $paymentIntent): bool
    {
        return PaymentIntent::STATUS_SUCCEEDED === $paymentIntent->status && $this->isChargeRefunded($paymentIntent);
    }

    /**
     * @see https://stripe.com/docs/payments/paymentintents/lifecycle
     */
    private function isSpecialCancelStatus(PaymentIntent $paymentIntent): bool
    {
        if (PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD !== $paymentIntent->status) {
            return false;
        }

        if (null === $paymentIntent->last_payment_error) {
            return false;
        }

        return true;
    }

    private function isChargeRefunded(PaymentIntent $paymentIntent): bool
    {
        $charge = $paymentIntent->latest_charge;
        if (null === $charge) {
            return false;
        }

        if (false === $charge instanceof Charge) {
            throw new \LogicException(sprintf(
                'To avoid too many API requests, we need to get access to the PaymentIntent->latest_charge object at this point.
                Please check that "%s" is expanding the Checkout/Session retrieval request with "payment_intent.latest_charge".',
                ExpandProvider::class,
            ));
        }

        return $charge->refunded;
    }
}
