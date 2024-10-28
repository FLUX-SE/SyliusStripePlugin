<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandHandler;

use FluxSE\SyliusStripePlugin\Command\AbstractNotifyPaymentRequest;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use FluxSE\SyliusStripePlugin\Processor\WebhookEventProcessorInterface;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\StripeClient;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class NotifyPaymentRequestHandler
{
    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private ClientFactoryInterface $stripeClientFactory,
        private ParamsProviderInterface $eventParamsProvider,
        private OptsProviderInterface $eventOptsProvider,
        private WebhookEventProcessorInterface $webhookProcessor,
        private PaymentTransitionProcessorInterface $paymentTransitionProcessor,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function __invoke(AbstractNotifyPaymentRequest $capturePaymentRequest): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($capturePaymentRequest);
        /** @var array{event: array<string, mixed>}|null $payload */
        $payload = $paymentRequest->getPayload();
        $data = $payload['event'] ?? [];
        /** @var string|null $id */
        $id = $data['id'] ?? null;
        if (null === $id) {
            throw new \LogicException('The payment request payload "[event][id]" is null.');
        }

        /** @var StripeClient $stripe */
        $stripe = $this->stripeClientFactory->createFromPaymentMethod($paymentRequest->getMethod());
        $params = $this->eventParamsProvider->getParams($paymentRequest, 'retrieve');
        $opts = $this->eventOptsProvider->getOpts($paymentRequest, 'retrieve');
        $event = $stripe->events->retrieve($id, $params, $opts);

        $this->webhookProcessor->process($paymentRequest, $event);

        $this->paymentTransitionProcessor->process($paymentRequest);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_COMPLETE,
        );
    }
}
