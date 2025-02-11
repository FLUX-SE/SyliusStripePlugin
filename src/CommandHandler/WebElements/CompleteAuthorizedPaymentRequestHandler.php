<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\WebElements;

use FluxSE\SyliusStripePlugin\Command\WebElements\CompleteAuthorizedPaymentRequest;
use FluxSE\SyliusStripePlugin\CommandHandler\FailedAwarePaymentRequestHandlerTrait;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CaptureManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CompleteAuthorizedPaymentRequestHandler
{
    use FailedAwarePaymentRequestHandlerTrait;

    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private RetrieveManagerInterface $retrieveWebElementsManager,
        private CaptureManagerInterface $captureWebElementsManager,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        StateMachineInterface $stateMachine,
    ) {
        $this->stateMachine = $stateMachine;
    }

    public function __invoke(CompleteAuthorizedPaymentRequest $completeAuthorizedPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($completeAuthorizedPaymentRequest);

        if (PaymentRequestInterface::STATE_PROCESSING === $paymentRequest->getState()) {
            return;
        }

        /** @var string|null $id */
        $id = $paymentRequest->getPayment()->getDetails()['id'] ?? null;
        if (null === $id) {
            $this->failWithReason(
                $paymentRequest,
                'An id is required to retrieve the related Stripe PaymentIntent.',
            );

            return;
        }

        $paymentIntent = $this->retrieveWebElementsManager->retrieve($paymentRequest, $id);
        if (PaymentIntent::STATUS_REQUIRES_CAPTURE !== $paymentIntent->status) {
            return;
        }

        $paymentIntent = $this->captureWebElementsManager->capture($paymentRequest, $paymentIntent->id);

        $paymentRequest->getPayment()->setDetails($paymentIntent->toArray());

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}
