<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Resolver;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

interface ExpressCheckoutPaymentMethodResolverInterface
{
    public function resolveForChannel(ChannelInterface $channel): ?PaymentMethodInterface;

    public function getPublishableKey(PaymentMethodInterface $paymentMethod): ?string;
}
