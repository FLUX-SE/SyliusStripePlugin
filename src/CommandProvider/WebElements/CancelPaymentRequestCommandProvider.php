<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandProvider\WebElements;

use FluxSE\SyliusStripePlugin\Command\WebElements\CancelPaymentRequest;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class CancelPaymentRequestCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return $paymentRequest->getAction() === PaymentRequestInterface::ACTION_CANCEL;
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        return new CancelPaymentRequest($paymentRequest->getId());
    }
}
