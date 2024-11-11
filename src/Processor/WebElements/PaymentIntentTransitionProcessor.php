<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor\WebElements;

use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentTransitions;

final readonly class PaymentIntentTransitionProcessor implements PaymentTransitionProcessorInterface
{
    public function __construct(
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest): void
    {
        $payment = $paymentRequest->getPayment();
        $details = $payment->getDetails();
        $paymentIntent = PaymentIntent::constructFrom($details);

        $transition = $this->getTransition($paymentIntent);
        if (null === $transition) {
            return;
        }

        if ($this->stateMachine->can($payment, PaymentTransitions::GRAPH, $transition)) {
            $this->stateMachine->apply($payment, PaymentTransitions::GRAPH, $transition);
        }
    }

    private function getTransition(PaymentIntent $paymentIntent): ?string
    {
        $status = $paymentIntent->status;
        if (PaymentIntent::STATUS_SUCCEEDED === $status) {
            return PaymentTransitions::TRANSITION_COMPLETE;
        }

        if (PaymentIntent::STATUS_REQUIRES_CAPTURE === $status) {
            return PaymentTransitions::TRANSITION_AUTHORIZE;
        }

        if (PaymentIntent::STATUS_PROCESSING === $status) {
            return PaymentTransitions::TRANSITION_PROCESS;
        }

        if ($this->isCanceledStatus($status) || $this->isSpecialCanceledStatus($paymentIntent)) {
            return PaymentTransitions::TRANSITION_CANCEL;
        }

        return null;
    }

    /**
     * @see https://stripe.com/docs/payments/paymentintents/lifecycle
     */
    private function isCanceledStatus(?string $status): bool
    {
        return PaymentIntent::STATUS_CANCELED === $status;
    }

    /**
     * @see https://stripe.com/docs/payments/paymentintents/lifecycle
     */
    protected function isSpecialCanceledStatus(PaymentIntent $paymentIntent): bool
    {
        $status = $paymentIntent->status;
        $lastPaymentError = $paymentIntent->last_payment_error;

        if (PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD === $status) {
            if (null !== $lastPaymentError) {
                return true;
            }
        }

        return false;
    }
}
