<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\WebElements;

use FluxSE\SyliusStripePlugin\Command\WebElements\RefundPaymentRequest;
use FluxSE\SyliusStripePlugin\CommandHandler\FailedAwarePaymentRequestHandlerTrait;
use FluxSE\SyliusStripePlugin\Manager\Refund\CreateManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\WebElements\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefundPaymentRequestHandler
{
    use FailedAwarePaymentRequestHandlerTrait;

    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private RetrieveManagerInterface $retrievePaymentIntentManager,
        private CreateManagerInterface $createRefundManager,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        StateMachineInterface $stateMachine,
    ) {
        $this->stateMachine = $stateMachine;
    }

    public function __invoke(RefundPaymentRequest $refundPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($refundPaymentRequest);

        /** @var string|null $id */
        $id = $paymentRequest->getPayment()->getDetails()['id'] ?? null;
        if (null === $id) {
            $this->failWithReason(
                $paymentRequest,
                'An id is required to retrieve the related Stripe PaymentIntent.',
            );

            return;
        }

        $paymentIntent = $this->retrievePaymentIntentManager->retrieve($paymentRequest, $id);
        if ($paymentIntent::STATUS_SUCCEEDED !== $paymentIntent->status) {
            $this->failWithReason(
                $paymentRequest,
                sprintf(
                    'Payment Intent status is "%s" instead of "%s".',
                    $paymentIntent->status,
                    $paymentIntent::STATUS_SUCCEEDED,
                ),
            );

            return;
        }

        if (0 >= $paymentIntent->amount) {
            $this->failWithReason(
                $paymentRequest,
                sprintf(
                    'Payment Intent amount is not greater than 0 (amount: %s)',
                    $paymentIntent->amount,
                ),
            );

            return;
        }

        $paymentRequest->setPayload([
            'payment_intent' => $id,
            'amount' => $refundPaymentRequest->getAmount(),
        ]);

        $refund = $this->createRefundManager->create($paymentRequest);

        $paymentRequest->setResponseData($refund->toArray());

        $paymentRequest->getPayment()->setDetails($paymentIntent->toArray());

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}
