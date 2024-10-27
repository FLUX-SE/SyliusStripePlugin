<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements;

use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

final readonly class CurrencyProvider implements CurrencyProviderInterface
{
    public function getCurrency(PaymentRequestInterface $paymentRequest): string
    {
        $currencyCode = $paymentRequest->getPayment()->getCurrencyCode();
        Assert::notNull($currencyCode, 'The currency code cannot be null.');

        return $currencyCode;
    }
}
