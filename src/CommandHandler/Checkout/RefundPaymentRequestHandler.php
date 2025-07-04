<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\RefundPaymentRequest;
use FluxSE\SyliusStripePlugin\CommandHandler\FailedAwarePaymentRequestHandlerTrait;
use FluxSE\SyliusStripePlugin\Manager\Checkout\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Refund\CreateManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use FluxSE\SyliusStripePlugin\Provider\Refund\PaymentIntentToRefundProviderInterface;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefundPaymentRequestHandler
{
    use FailedAwarePaymentRequestHandlerTrait;

    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private RetrieveManagerInterface $retrieveCheckoutManager,
        private PaymentIntentToRefundProviderInterface $refundPaymentProvider,
        private PaymentIntentToRefundProviderInterface $refundSubscriptionInitProvider,
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
                'An id is required to retrieve the related Stripe Checkout/Session.',
            );

            return;
        }

        $session = $this->retrieveCheckoutManager->retrieve($paymentRequest, $id);
        if ($session::PAYMENT_STATUS_PAID !== $session->payment_status) {
            $this->failWithReason(
                $paymentRequest,
                sprintf(
                    'Checkout Session payment status is "%s" instead of "%s".',
                    $session->payment_status,
                    $session::PAYMENT_STATUS_PAID,
                ),
            );

            return;
        }

        if (0 >= $session->amount_total) {
            $this->failWithReason(
                $paymentRequest,
                sprintf(
                    'Checkout Session amount total is not greater than 0 (amount_total: %s)',
                    $session->amount_total,
                ),
            );

            return;
        }

        $paymentIntent = $this->getRelatedPaymentIntent($paymentRequest);
        if (null === $paymentIntent) {
            $this->failWithReason(
                $paymentRequest,
                sprintf(
                    'Unable to find the related payment intent for the Checkout Session "%s".',
                    $id,
                ),
            );

            return;
        }

        $paymentRequest->setPayload([
            'payment_intent' => $paymentIntent,
            'amount' => $refundPaymentRequest->getAmount(),
        ]);

        $refund = $this->createRefundManager->create($paymentRequest);
        $session = $this->retrieveCheckoutManager->retrieve($paymentRequest, $id);

        $paymentRequest->setResponseData($refund->toArray());

        $paymentRequest->getPayment()->setDetails($session->toArray());

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }

    private function getRelatedPaymentIntent(PaymentRequestInterface $paymentRequest): null|string|PaymentIntent
    {
        $paymentIntent = $this->refundPaymentProvider->provide($paymentRequest);
        if (null === $paymentIntent) {
            $paymentIntent = $this->refundSubscriptionInitProvider->provide($paymentRequest);
        }

        return $paymentIntent;
    }
}
