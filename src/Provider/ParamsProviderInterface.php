<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T of ApiResource
 */
interface ParamsProviderInterface
{
    /**
     * @return array<key-of<T>, mixed>|null
     */
    public function getParams(PaymentRequestInterface $paymentRequest): ?array;
}
