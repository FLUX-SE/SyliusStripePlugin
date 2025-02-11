<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandProvider\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\StatusPaymentRequest;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class StatusPaymentRequestCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return true;
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        return new StatusPaymentRequest($paymentRequest->getId());
    }
}
