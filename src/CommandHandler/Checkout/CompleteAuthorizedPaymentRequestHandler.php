<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\CompleteAuthorizedPaymentRequest;
use FluxSE\SyliusStripePlugin\CommandHandler\FailedAwarePaymentRequestHandlerTrait;
use FluxSE\SyliusStripePlugin\Manager\Checkout\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CaptureManagerInterface;
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
        private RetrieveManagerInterface $retrieveCheckoutManager,
        private CaptureManagerInterface $captureCheckoutManager,
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
                'An id is required to retrieve the related Stripe Checkout/Session.',
            );

            return;
        }

        $session = $this->retrieveCheckoutManager->retrieve($paymentRequest, $id);
        $pi = $session->payment_intent;
        if ($pi instanceof PaymentIntent) {
            $pi = $pi->id;
        }

        if (null === $pi) {
            $this->failWithReason(
                $paymentRequest,
                sprintf(
                    'An id is required to retrieve the related Stripe PaymentIntent in the Session (ID:%s).',
                    $id,
                ),
            );

            return;
        }

        $paymentIntent = $this->captureCheckoutManager->capture($paymentRequest, $pi);

        $session->payment_intent = $paymentIntent;

        $paymentRequest->getPayment()->setDetails($session->toArray());

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}
