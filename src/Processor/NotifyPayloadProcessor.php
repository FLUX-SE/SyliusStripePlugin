<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor;

use Sylius\Bundle\PaymentBundle\Processor\NotifyPayloadProcessorInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\Request;

final class NotifyPayloadProcessor implements NotifyPayloadProcessorInterface
{
    /**
     * @param string[] $supportedFactories
     */
    public function __construct(
        private NotifyPayloadProcessorInterface $decoratedNotifyPayloadProcessor,
        private array $supportedFactories,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest, Request $request): void
    {
        $factoryName = $paymentRequest->getMethod()->getGatewayConfig()?->getFactoryName() ?? '';
        if (in_array($factoryName, $this->supportedFactories, true)) {
            $data = $request->toArray();

            $paymentRequest->setPayload([
                'event' => $data,
            ]);
        }

        $this->decoratedNotifyPayloadProcessor->process($paymentRequest, $request);
    }
}
