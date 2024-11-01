<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager;

use Stripe\ApiResource;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 */
interface CreateManagerInterface
{
    /**
     * @return T
     */
    public function create(PaymentRequestInterface $paymentRequest): ApiResource;
}
