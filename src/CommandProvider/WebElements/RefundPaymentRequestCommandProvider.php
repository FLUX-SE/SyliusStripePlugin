<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandProvider\WebElements;

use FluxSE\SyliusStripePlugin\Command\WebElements\RefundPaymentRequest;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class RefundPaymentRequestCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return true;
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        $payload = $paymentRequest->getPayload();
        $amount = null;
        if (is_array($payload)) {
            /** @var null|int $amount */
            $amount = $payload['amount'] ?? null;
        }

        return new RefundPaymentRequest($paymentRequest->getId(), $amount);
    }
}
