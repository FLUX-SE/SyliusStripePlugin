<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\WebElements;

use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

final readonly class AmountProvider implements AmountProviderInterface
{
    public function getAmount(PaymentRequestInterface $paymentRequest): int
    {
        $amount = $paymentRequest->getPayment()->getAmount();
        Assert::notNull($amount, 'The amount cannot be null.');

        return $amount;
    }
}
