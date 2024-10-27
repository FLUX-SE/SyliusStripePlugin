<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class CustomerEmailProvider implements CustomerEmailProviderInterface
{
    public function getCustomerEmail(PaymentRequestInterface $paymentRequest): ?string
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();

        return $order?->getCustomer()?->getEmail();
    }
}
