<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor\Checkout;

use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Stripe\Checkout\Session;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentTransitions;

final readonly class CheckoutSessionTransitionProcessor implements PaymentTransitionProcessorInterface
{
    public function __construct(
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest): void
    {
        $payment = $paymentRequest->getPayment();
        $details = $payment->getDetails();
        $session = Session::constructFrom($details);

        $transition = $this->getTransition($session);

        if (null === $transition) {
            return;
        }

        if ($this->stateMachine->can($payment, PaymentTransitions::GRAPH, $transition)) {
            $this->stateMachine->apply($payment, PaymentTransitions::GRAPH, $transition);
        }
    }

    private function getTransition(Session $session): ?string
    {
        $status = $session->status;
        $paymentStatus = $session->payment_status;

        if ($this->isCompleteStatus($status, $paymentStatus)) {
            return PaymentTransitions::TRANSITION_COMPLETE;
        }

        if ($this->isFailStatus($status)) {
            return PaymentTransitions::TRANSITION_FAIL;
        }

        if ($this->isProcessStatus($status, $paymentStatus)) {
            return PaymentTransitions::TRANSITION_PROCESS;
        }

        return null;
    }

    private function isCompleteStatus(?string $status, string $paymentStatus): bool
    {
        if (Session::STATUS_COMPLETE !== $status) {
            return false;
        }

        return Session::PAYMENT_STATUS_UNPAID !== $paymentStatus;
    }

    private function isFailStatus(?string $status): bool
    {
        return Session::STATUS_EXPIRED === $status;
    }

    private function isProcessStatus(?string $status, string $paymentStatus): bool
    {
        if (Session::STATUS_COMPLETE !== $status) {
            return false;
        }

        return Session::PAYMENT_STATUS_UNPAID === $paymentStatus;
    }
}
