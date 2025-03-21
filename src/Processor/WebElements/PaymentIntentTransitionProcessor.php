<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor\WebElements;

use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use FluxSE\SyliusStripePlugin\Provider\Transition\WebElements\PaymentIntentTransitionProviderInterface;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentTransitions;

final readonly class PaymentIntentTransitionProcessor implements PaymentTransitionProcessorInterface
{
    public function __construct(
        private PaymentIntentTransitionProviderInterface $paymentIntentTransitionProvider,
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
        if ($this->paymentIntentTransitionProvider->isAuthorize($paymentIntent)) {
            return PaymentTransitions::TRANSITION_AUTHORIZE;
        }

        if ($this->paymentIntentTransitionProvider->isComplete($paymentIntent)) {
            return PaymentTransitions::TRANSITION_COMPLETE;
        }

        if ($this->paymentIntentTransitionProvider->isFail($paymentIntent)) {
            return PaymentTransitions::TRANSITION_FAIL;
        }

        if ($this->paymentIntentTransitionProvider->isCancel($paymentIntent)) {
            return PaymentTransitions::TRANSITION_CANCEL;
        }

        if ($this->paymentIntentTransitionProvider->isRefund($paymentIntent)) {
            return PaymentTransitions::TRANSITION_REFUND;
        }

        if ($this->paymentIntentTransitionProvider->isProcess($paymentIntent)) {
            return PaymentTransitions::TRANSITION_PROCESS;
        }

        return null;
    }
}
