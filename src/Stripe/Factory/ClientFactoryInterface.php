<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Factory;

use Stripe\StripeClientInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;

interface ClientFactoryInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function createNew(array $config): StripeClientInterface;

    public function createFromPaymentMethod(PaymentMethodInterface $paymentMethod): StripeClientInterface;
}
