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

        $transition = PaymentTransitions::TRANSITION_CANCEL;
        if ($session->payment_status === Session::PAYMENT_STATUS_PAID) {
            $transition = PaymentTransitions::TRANSITION_COMPLETE;
        }

        if ($session->status === Session::STATUS_OPEN) {
            return;
        }

        if ($this->stateMachine->can($payment, PaymentTransitions::GRAPH, $transition)) {
            $this->stateMachine->apply(
                $payment,
                PaymentTransitions::GRAPH,
                $transition,
            );
        }
    }
}
