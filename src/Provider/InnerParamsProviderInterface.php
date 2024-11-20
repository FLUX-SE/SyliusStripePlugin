<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as StripeObject
 */
interface InnerParamsProviderInterface
{
    /**
     * @param array<key-of<T>, mixed> $params
     */
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void;
}
