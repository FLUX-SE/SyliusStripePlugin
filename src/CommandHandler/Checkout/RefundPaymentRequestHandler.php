<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\RefundPaymentRequest;
use FluxSE\SyliusStripePlugin\Manager\Checkout\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Refund\CreateManagerInterface;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class RefundPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private RetrieveManagerInterface $retrieveCheckoutManager,
        private CreateManagerInterface $createRefundManager,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function __invoke(RefundPaymentRequest $refundPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($refundPaymentRequest);

        /** @var string|null $id */
        $id = $paymentRequest->getPayment()->getDetails()['id'] ?? null;
        Assert::notNull($id, 'An id is required to retrieve the related Stripe Checkout Session.');

        $session = $this->retrieveCheckoutManager->retrieve($paymentRequest, $id);
        if ($session::PAYMENT_STATUS_PAID !== $session->payment_status) {
            $reason = sprintf(
                'Checkout Session payment status is "%s" instead of "%s".',
                $session->payment_status,
                $session::PAYMENT_STATUS_PAID
            );
            $this->setFailed($paymentRequest, $reason);
            return;
        }

        if (0 >= $session->amount_total) {
            $reason = sprintf(
                'Checkout Session amount total is not greater than 0 (amount_total: %s)',
                $session->amount_total
            );
            $this->setFailed($paymentRequest, $reason);
            return;
        }

        $paymentRequest->setPayload([
            'payment_intent' => $session->payment_intent,
            'amount' => $refundPaymentRequest->getAmount(),
        ]);

        $refund = $this->createRefundManager->create($paymentRequest);

        $paymentRequest->setResponseData($refund->toArray());

        $paymentRequest->getPayment()->setDetails($session->toArray());

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }

    private function setFailed(
        PaymentRequestInterface $paymentRequest,
        string $reason
    ): void {
        $paymentRequest->setResponseData([
            'reason' => $reason,
        ]);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_FAIL,
        );
    }
}
