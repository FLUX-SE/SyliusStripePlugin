<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler;

use FluxSE\SyliusStripePlugin\Command\AbstractStatusPaymentRequest;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class StatusPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function __invoke(AbstractStatusPaymentRequest $statusPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($statusPaymentRequest);

        $details = $paymentRequest->getPayment()->getDetails();

        $paymentRequest->getPayment()->setDetails($details);

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}
