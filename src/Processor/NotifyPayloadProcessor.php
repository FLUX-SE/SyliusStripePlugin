<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor;

use Sylius\Bundle\PaymentBundle\Processor\NotifyPayloadProcessorInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\Request;

final class NotifyPayloadProcessor implements NotifyPayloadProcessorInterface
{
    public function __construct(
        private NotifyPayloadProcessorInterface $decoratedNotifyPayloadProcessor,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest, Request $request): void
    {
        $data = $request->toArray();

        $paymentRequest->setPayload([
            'event' => $data,
        ]);

        $this->decoratedNotifyPayloadProcessor->process($paymentRequest, $request);
    }
}
