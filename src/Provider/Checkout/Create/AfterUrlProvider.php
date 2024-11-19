<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use Stripe\Checkout\Session;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<Session>
 */
final readonly class AfterUrlProvider implements DetailsProviderInterface
{
    public function __construct(
        private AfterUrlProviderInterface $defaultAfterPayUrlProvider,
    ) {
    }

    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        /** @var string[] $payload */
        $payload = $paymentRequest->getPayload();

        foreach ([
            AfterUrlProviderInterface::SUCCESS_URL,
            AfterUrlProviderInterface::CANCEL_URL,
        ] as $type) {
            $details[$type] = $payload[$type] ?? $this->defaultAfterPayUrlProvider->getUrl($paymentRequest, $type);
        }
    }
}
