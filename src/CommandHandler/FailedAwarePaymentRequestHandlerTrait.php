<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler;

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;

trait FailedAwarePaymentRequestHandlerTrait
{
    private readonly StateMachineInterface $stateMachine;

    private function failWithReason(
        PaymentRequestInterface $paymentRequest,
        string $reason,
    ): void {
        $paymentRequest->setResponseData([
            'reason' => $reason,
        ]);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_FAIL,
        );
    }
}
