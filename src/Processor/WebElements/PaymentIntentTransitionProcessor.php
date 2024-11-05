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

        $transition = PaymentTransitions::TRANSITION_CANCEL;
        if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
            $transition = PaymentTransitions::TRANSITION_COMPLETE;
        }
        if ($paymentIntent->status === PaymentIntent::STATUS_PROCESSING) {
            $transition = PaymentTransitions::TRANSITION_PROCESS;
        }

        if (in_array(
            $paymentIntent->status,
            [
                PaymentIntent::STATUS_REQUIRES_ACTION,
                PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            ],
            true,
        )) {
            return;
        }

        if ($this->stateMachine->can($payment, PaymentTransitions::GRAPH, $transition)) {
            $this->stateMachine->apply($payment, PaymentTransitions::GRAPH, $transition);
        }
    }
}
