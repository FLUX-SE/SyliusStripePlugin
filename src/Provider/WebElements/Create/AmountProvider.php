<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements\Create;

use FluxSE\SyliusStripePlugin\Provider\InnerParamsProviderInterface;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements InnerParamsProviderInterface<PaymentIntent>
 */
final readonly class AmountProvider implements InnerParamsProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        $amount = $paymentRequest->getPayment()->getAmount();
        if (null === $amount) {
            return;
        }

        $params['amount'] = $amount;
    }
}
