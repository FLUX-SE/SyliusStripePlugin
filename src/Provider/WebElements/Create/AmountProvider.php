<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements\Create;

use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<PaymentIntent>
 */
final readonly class AmountProvider implements DetailsProviderInterface
{

    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        $amount = $paymentRequest->getPayment()->getAmount();
        if (null === $amount) {
            return;
        }

        $details['amount'] = $amount;
    }
}
