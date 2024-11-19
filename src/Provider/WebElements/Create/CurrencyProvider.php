<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements\Create;

use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

/**
 * @implements DetailsProviderInterface<PaymentIntent>
 */
final readonly class CurrencyProvider implements DetailsProviderInterface
{
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        $currencyCode = $paymentRequest->getPayment()->getCurrencyCode();
        if (null === $currencyCode) {
            return;
        }

        $details['currency'] = $currencyCode;
    }
}
