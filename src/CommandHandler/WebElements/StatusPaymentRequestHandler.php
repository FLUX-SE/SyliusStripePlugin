<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler\WebElements;

use FluxSE\SyliusStripePlugin\Command\WebElements\StatusPaymentRequest;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\StripeClient;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class StatusPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private ClientFactoryInterface $stripeClientFactory,
        private ParamsProviderInterface $paymentIntentParamsProvider,
        private OptsProviderInterface $paymentIntentOptsProvider,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function __invoke(StatusPaymentRequest $statusPaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($statusPaymentRequest);

        /** @var StripeClient $stripe */
        $stripe = $this->stripeClientFactory->createFromPaymentMethod($paymentRequest->getMethod());

        $details = $paymentRequest->getPayment()->getDetails();
        $params = $this->paymentIntentParamsProvider->getParams($paymentRequest, 'retrieve');
        $opts = $this->paymentIntentOptsProvider->getOpts($paymentRequest, 'retrieve');
        $paymentIntent = $stripe->paymentIntents->retrieve($details['id'], $params, $opts);

        $payment = $paymentRequest->getPayment();
        $data = $paymentIntent->toArray();
        $paymentRequest->setResponseData($data);
        $payment->setDetails($data);

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}