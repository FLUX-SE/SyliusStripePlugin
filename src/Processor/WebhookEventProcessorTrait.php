<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor;

use Stripe\Event;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

trait WebhookEventProcessorTrait
{
    abstract protected function getSupportedEventTypes(): array;

    public function supports(PaymentRequestInterface $paymentRequest, Event $event): bool
    {
        return in_array($event->type, $this->getSupportedEventTypes(), true);
    }
}
