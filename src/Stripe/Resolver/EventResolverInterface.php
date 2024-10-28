<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Resolver;

use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Symfony\Component\HttpFoundation\Request;

interface EventResolverInterface
{
    /**
     * @throws SignatureVerificationException
     */
    public function resolve(Request $request, PaymentMethodInterface $paymentMethod): Event;
}
