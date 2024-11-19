<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<Session>
 */
final class PaymentIntentDataProvider implements DetailsProviderInterface
{
    /**
     * @param DetailsProviderInterface<PaymentIntent>[] $detailsProviders
     */
    public function __construct(
        private iterable $detailsProviders,
    ) {
    }

    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        /** @var array<key-of<PaymentIntent>, mixed> $paymentIntentData */
        $paymentIntentData = [];
        foreach ($this->detailsProviders as $detailsProvider) {
            $detailsProvider->getDetails($paymentRequest, $paymentIntentData);
        }

        if ([] === $paymentIntentData) {
            return;
        }

        if (false === isset($details['payment_intent_data'])) {
            $details['payment_intent_data'] = [];
        }

        $details['payment_intent_data'] += $paymentIntentData;
    }
}
