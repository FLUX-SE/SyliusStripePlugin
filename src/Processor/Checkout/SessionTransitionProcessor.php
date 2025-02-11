<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor\Checkout;

use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\SessionTransitionProviderInterface;
use Stripe\Checkout\Session;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentTransitions;

final readonly class SessionTransitionProcessor implements PaymentTransitionProcessorInterface
{
    public function __construct(
        private SessionTransitionProviderInterface $sessionTransitionProvider,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest): void
    {
        $payment = $paymentRequest->getPayment();

        $transition = $this->getTransition($payment);

        if (null === $transition) {
            return;
        }

        if ($this->stateMachine->can($payment, PaymentTransitions::GRAPH, $transition)) {
            $this->stateMachine->apply($payment, PaymentTransitions::GRAPH, $transition);
        }
    }

    private function getTransition(PaymentInterface $payment): ?string
    {
        $details = $payment->getDetails();
        $session = Session::constructFrom($details);

        if ($this->sessionTransitionProvider->isAuthorize($session)) {
            return PaymentTransitions::TRANSITION_AUTHORIZE;
        }

        if ($this->sessionTransitionProvider->isComplete($session)) {
            return PaymentTransitions::TRANSITION_COMPLETE;
        }

        if ($this->sessionTransitionProvider->isFail($session)) {
            return PaymentTransitions::TRANSITION_FAIL;
        }

        if ($this->sessionTransitionProvider->isCancel($session)) {
            return PaymentTransitions::TRANSITION_CANCEL;
        }

        if ($this->sessionTransitionProvider->isRefund($session)) {
            return PaymentTransitions::TRANSITION_REFUND;
        }

        if ($this->sessionTransitionProvider->isProcess($session)) {
            return PaymentTransitions::TRANSITION_PROCESS;
        }

        return null;
    }
}
