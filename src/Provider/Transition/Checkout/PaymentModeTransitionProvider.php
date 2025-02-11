<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Transition\Checkout;

use FluxSE\SyliusStripePlugin\Provider\ExpandProvider;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

final class PaymentModeTransitionProvider implements SessionModeTransitionProviderInterface
{
    public function isAuthorize(Session $session): bool
    {
        $paymentIntent = $this->getPaymentIntent($session);

        return $paymentIntent->status === PaymentIntent::STATUS_REQUIRES_CAPTURE;
    }

    public function isComplete(Session $session): bool
    {
        $paymentIntent = $this->getPaymentIntent($session);

        return $paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED;
    }

    public function isFail(Session $session): bool
    {
        return false;
    }

    public function isProcess(Session $session): bool
    {
        $paymentIntent = $this->getPaymentIntent($session);

        return $paymentIntent->status === PaymentIntent::STATUS_PROCESSING;
    }

    public function isCancel(Session $session): bool
    {
        $paymentIntent = $this->getPaymentIntent($session);

        return $paymentIntent->status === PaymentIntent::STATUS_CANCELED || $this->isSpecialCancelStatus($paymentIntent);
    }

    public function isRefund(Session $session): bool
    {
        $paymentIntent = $this->getPaymentIntent($session);

        return $this->isComplete($session) && $this->isChargeRefunded($paymentIntent);
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

    private function getPaymentIntent(Session $session): PaymentIntent
    {
        $supportedMode = self::getSupportedMode();
        if ($supportedMode !== $session->mode) {
            throw new \LogicException(
                sprintf(
                    '%s only able to provide "%s" Checkout Session mode, "%s" found.',
                    __CLASS__,
                    $supportedMode,
                    $session->mode,
                ),
            );
        }

        $paymentIntent = $session->payment_intent;
        if (false === $paymentIntent instanceof PaymentIntent) {
            throw new \LogicException(sprintf(
                'To avoid too many API requests, we need to get access to a PaymentIntent object at this point.
                Please check that "%s" is expanding the Checkout/Session retrieval request with "payment_intent".',
                ExpandProvider::class,
            ));
        }

        return $paymentIntent;
    }

    public static function getSupportedMode(): string
    {
        return Session::MODE_PAYMENT;
    }
}
