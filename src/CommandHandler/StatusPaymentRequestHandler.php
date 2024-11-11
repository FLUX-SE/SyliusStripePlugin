<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler;

use FluxSE\SyliusStripePlugin\Command\AbstractStatusPaymentRequest;
use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class StatusPaymentRequestHandler
{
    /**
     * @param RetrieveManagerInterface<Session|PaymentIntent> $retrieveManager
     */
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private RetrieveManagerInterface $retrieveManager,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function __invoke(AbstractStatusPaymentRequest $statusPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($statusPaymentRequest);

        /** @var string|null $id */
        $id = $paymentRequest->getPayment()->getDetails()['id'] ?? null;
        Assert::notNull($id, 'An id is required to retrieve the related Stripe API Resource (Session|PaymentIntent).');

        $stripeApiResource = $this->retrieveManager->retrieve($paymentRequest, $id);

        $paymentRequest->getPayment()->setDetails($stripeApiResource->toArray());

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}
