<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements\Create;

use FluxSE\SyliusStripePlugin\Provider\InnerParamsProviderInterface;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

/**
 * @implements InnerParamsProviderInterface<PaymentIntent>
 */
final readonly class CurrencyProvider implements InnerParamsProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        $currencyCode = $paymentRequest->getPayment()->getCurrencyCode();
        if (null === $currencyCode) {
            return;
        }

        $params['currency'] = $currencyCode;
    }
}
