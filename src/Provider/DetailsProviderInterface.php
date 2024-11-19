<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 */
interface DetailsProviderInterface
{
    /**
     * @param array<key-of<T>, mixed> $details
     */
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void;
}
