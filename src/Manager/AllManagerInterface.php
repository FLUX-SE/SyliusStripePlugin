<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager;

use Stripe\ApiResource;
use Stripe\Collection;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 */
interface AllManagerInterface
{
    /**
     * @return Collection<T>
     */
    public function all(PaymentRequestInterface $paymentRequest): Collection;
}
