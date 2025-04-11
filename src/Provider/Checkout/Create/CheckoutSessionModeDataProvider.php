<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\InnerParamsProviderInterface;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\Subscription;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements InnerParamsProviderInterface<Session>
 */
final class CheckoutSessionModeDataProvider implements InnerParamsProviderInterface
{
    /**
     * @param InnerParamsProviderInterface<PaymentIntent|SetupIntent|Subscription>[] $detailsProviders
     */
    public function __construct(
        private string $mode,
        private iterable $detailsProviders,
    ) {
    }

    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        if (isset($params['mode']) && $this->mode !== $params['mode']) {
            return;
        }

        /** @var array<key-of<PaymentIntent|SetupIntent|Subscription>, mixed> $modeData */
        $modeData = [];
        foreach ($this->detailsProviders as $detailsProvider) {
            $detailsProvider->provide($paymentRequest, $modeData);
        }

        if ([] === $modeData) {
            return;
        }

        $dataKey = match ($this->mode) {
            Session::MODE_PAYMENT => 'payment_intent_data',
            Session::MODE_SETUP => 'setup_intent_data',
            Session::MODE_SUBSCRIPTION => 'subscription_data',
            default => throw new \InvalidArgumentException(sprintf('Invalid Stripe Session mode: %s', $this->mode)),
        };

        if (false === isset($params[$dataKey])) {
            $params[$dataKey] = [];
        }

        $params[$dataKey] += $modeData;
    }
}
