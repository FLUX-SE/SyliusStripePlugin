<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\ExpirePaymentRequest;
use FluxSE\SyliusStripePlugin\CommandHandler\FailedAwarePaymentRequestHandlerTrait;
use FluxSE\SyliusStripePlugin\Manager\Checkout\ExpireManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Checkout\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Stripe\Checkout\Session;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ExpirePaymentRequestHandler
{
    use FailedAwarePaymentRequestHandlerTrait;

    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private RetrieveManagerInterface $retrieveCheckoutManager,
        private ExpireManagerInterface $expireCheckoutManager,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        StateMachineInterface $stateMachine,
    ) {
        $this->stateMachine = $stateMachine;
    }

    public function __invoke(ExpirePaymentRequest $expirePaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($expirePaymentRequest);

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
        if (Session::STATUS_OPEN !== $session->status) {
            $this->failWithReason(
                $paymentRequest,
                sprintf('The session "%s" is not open.', $session->id),
            );

            return;
        }

        $session = $this->expireCheckoutManager->expire($paymentRequest, $id);

        $paymentRequest->getPayment()->setDetails($session->toArray());

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}
