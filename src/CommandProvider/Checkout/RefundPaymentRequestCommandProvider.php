<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandProvider\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\RefundPaymentRequest;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class RefundPaymentRequestCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return $paymentRequest->getAction() === PaymentRequestInterface::ACTION_REFUND;
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        return new RefundPaymentRequest($paymentRequest->getId());
    }
}
