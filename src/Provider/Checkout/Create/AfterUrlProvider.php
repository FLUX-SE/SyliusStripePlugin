<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\InnerParamsProviderInterface;
use Stripe\Checkout\Session;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements InnerParamsProviderInterface<Session>
 */
final readonly class AfterUrlProvider implements InnerParamsProviderInterface
{
    public function __construct(
        private AfterUrlProviderInterface $defaultAfterPayUrlProvider,
    ) {
    }

    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        /** @var string[] $payload */
        $payload = $paymentRequest->getPayload();

        foreach ([
            AfterUrlProviderInterface::SUCCESS_URL,
            AfterUrlProviderInterface::CANCEL_URL,
        ] as $type) {
            $params[$type] = $payload[$type] ?? $this->defaultAfterPayUrlProvider->getUrl($paymentRequest, $type);
        }
    }
}
