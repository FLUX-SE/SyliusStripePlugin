<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor;

use Stripe\Event;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class CompositeWebhookEventProcessor implements WebhookEventProcessorInterface
{
    /**
     * @param WebhookEventProcessorInterface[] $webhookEventProcessors
     */
    public function __construct(
        private iterable $webhookEventProcessors,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest, Event $event): void
    {
        foreach ($this->webhookEventProcessors as $webhookEventProcessor) {
            if ($webhookEventProcessor->supports($paymentRequest, $event)) {
                $webhookEventProcessor->process($paymentRequest, $event);
            }
        }
    }

    public function supports(PaymentRequestInterface $paymentRequest, Event $event): bool
    {
        return true;
    }
}
