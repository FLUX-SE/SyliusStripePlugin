<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\WebElements;

use FluxSE\SyliusStripePlugin\Command\WebElements\CancelPaymentRequest;
use FluxSE\SyliusStripePlugin\CommandHandler\FailedAwarePaymentRequestHandlerTrait;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CancelManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelPaymentRequestHandler
{
    use FailedAwarePaymentRequestHandlerTrait;

    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private RetrieveManagerInterface $retrieveManager,
        private CancelManagerInterface $cancelManager,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        StateMachineInterface $stateMachine,
    ) {
        $this->stateMachine = $stateMachine;
    }

    public function __invoke(CancelPaymentRequest $cancelPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($cancelPaymentRequest);

        /** @var string|null $id */
        $id = $paymentRequest->getPayment()->getDetails()['id'] ?? null;
        if (null === $id) {
            $this->failWithReason(
                $paymentRequest,
                'An id is required to retrieve the related Stripe PaymentIntent.',
            );

            return;
        }

        $paymentIntent = $this->retrieveManager->retrieve($paymentRequest, $id);

        if (in_array($paymentIntent->status, [
            PaymentIntent::STATUS_SUCCEEDED,
            PaymentIntent::STATUS_CANCELED,
        ], true)) {
            $this->failWithReason(
                $paymentRequest,
                sprintf('The Payment Intent "%s" is not cancelable.', $paymentIntent->id),
            );

            return;
        }

        $paymentIntent = $this->cancelManager->cancel($paymentRequest, $id);

        $paymentRequest->getPayment()->setDetails($paymentIntent->toArray());

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}
