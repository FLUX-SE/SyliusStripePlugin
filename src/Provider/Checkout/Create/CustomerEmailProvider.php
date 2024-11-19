<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use Stripe\Checkout\Session;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<Session>
 */
final readonly class CustomerEmailProvider implements DetailsProviderInterface
{
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();

        $email = $order?->getCustomer()?->getEmail();

        if(null === $email) {
            return;
        }

        $details['customer_email'] = $email;
    }
}
