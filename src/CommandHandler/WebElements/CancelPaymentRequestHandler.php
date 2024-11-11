<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\WebElements;

use FluxSE\SyliusStripePlugin\Command\WebElements\CancelPaymentRequest;
use FluxSE\SyliusStripePlugin\Manager\WebElements\CancelManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class CancelPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private RetrieveManagerInterface $retrieveManager,
        private CancelManagerInterface $cancelManager,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function __invoke(CancelPaymentRequest $cancelPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($cancelPaymentRequest);

        /** @var string|null $id */
        $id = $paymentRequest->getPayment()->getDetails()['id'] ?? null;
        Assert::notNull($id, 'An id is required to retrieve the related Stripe PaymentIntent.');

        $paymentIntent = $this->retrieveManager->retrieve($paymentRequest, $id);

        if (false === in_array($paymentIntent->status, [
            $paymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            $paymentIntent::STATUS_REQUIRES_CAPTURE,
            $paymentIntent::STATUS_REQUIRES_CONFIRMATION,
            $paymentIntent::STATUS_REQUIRES_ACTION,
            $paymentIntent::STATUS_PROCESSING, // rare case @see https://docs.stripe.com/docs/payments/intents
        ], true)) {
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
