<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Transition\Checkout;

use FluxSE\SyliusStripePlugin\Provider\ExpandProvider;
use FluxSE\SyliusStripePlugin\Provider\Transition\WebElements\PaymentIntentTransitionProviderInterface;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use Stripe\PaymentIntent;

final class SubscriptionModeTransitionProvider implements SessionModeTransitionProviderInterface
{
    public function __construct(
        private readonly PaymentIntentTransitionProviderInterface $paymentIntentTransitionProvider,
    ) {
    }

    public function isAuthorize(Session $session): bool
    {
        return $this->paymentIntentTransitionProvider->isAuthorize($this->getPaymentIntent($session));
    }

    public function isComplete(Session $session): bool
    {
        $paymentIntent = $this->getPaymentIntent($session);

        return $this->paymentIntentTransitionProvider->isComplete($paymentIntent) &&
            Session::PAYMENT_STATUS_UNPAID !== $session->payment_status;
    }

    public function isFail(Session $session): bool
    {
        return false;
    }

    public function isProcess(Session $session): bool
    {
        $paymentIntent = $this->getPaymentIntent($session);

        return $this->paymentIntentTransitionProvider->isProcess($paymentIntent) &&
            Session::PAYMENT_STATUS_UNPAID === $session->payment_status;
    }

    public function isCancel(Session $session): bool
    {
        return $this->paymentIntentTransitionProvider->isCancel($this->getPaymentIntent($session));
    }

    public function isRefund(Session $session): bool
    {
        $paymentIntent = $this->getPaymentIntent($session);

        return $this->paymentIntentTransitionProvider->isRefund($paymentIntent) &&
            Session::PAYMENT_STATUS_UNPAID !== $session->payment_status;
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

        // For subscription mode, the payment intent is in the invoice
        $invoice = $session->invoice;
        if (false === $invoice instanceof Invoice) {
            throw new \LogicException(sprintf(
                'To avoid too many API requests, we need to get access to an Invoice object at this point.
                Please check that "%s" is expanding the Checkout/Session retrieval request with "invoice".',
                ExpandProvider::class,
            ));
        }

        $paymentIntent = $invoice->payment_intent;
        if (false === $paymentIntent instanceof PaymentIntent) {
            throw new \LogicException(sprintf(
                'To avoid too many API requests, we need to get access to a PaymentIntent object at this point.
                Please check that "%s" is expanding the Checkout/Session retrieval request with "invoice.payment_intent".',
                ExpandProvider::class,
            ));
        }

        return $paymentIntent;
    }

    public static function getSupportedMode(): string
    {
        return Session::MODE_SUBSCRIPTION;
    }
}
