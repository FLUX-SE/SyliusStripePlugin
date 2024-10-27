<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\StatusPaymentRequest;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\StripeClient;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Sylius\Component\Payment\PaymentTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class StatusPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private ClientFactoryInterface $stripeClientFactory,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function __invoke(StatusPaymentRequest $statusPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($statusPaymentRequest);

        /** @var StripeClient $stripe */
        $stripe = $this->stripeClientFactory->createFromPaymentMethod($paymentRequest->getMethod());

        $details = $paymentRequest->getPayment()->getDetails();
        $session = $stripe->checkout->sessions->retrieve($details['id']);

        $payment = $paymentRequest->getPayment();
        $data = $session->toArray();
        $paymentRequest->setResponseData($data);
        $payment->setDetails($data);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );

        $transition = PaymentTransitions::TRANSITION_CANCEL;
        if ($session->payment_status !== $session::PAYMENT_STATUS_UNPAID) {
            $transition = PaymentTransitions::TRANSITION_COMPLETE;
        }

        $this->stateMachine->apply(
            $payment,
            PaymentTransitions::GRAPH,
            $transition,
        );
    }
}
