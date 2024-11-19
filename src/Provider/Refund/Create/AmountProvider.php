<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund\Create;

use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use Stripe\Refund;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<Refund>
 */
final readonly class AmountProvider implements DetailsProviderInterface
{
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        $payload = $paymentRequest->getPayload();
        if (false === is_array($payload)) {
            return;
        }

        $amount = $payload['amount'] ?? null;
        if (null === $amount) {
            return;
        }

        $details['amount'] = $amount;
    }
}
