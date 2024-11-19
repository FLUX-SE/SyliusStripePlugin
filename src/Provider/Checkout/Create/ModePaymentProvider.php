<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use Stripe\Checkout\Session;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<Session>
 */
final class ModePaymentProvider implements DetailsProviderInterface
{
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        $details['mode'] = Session::MODE_PAYMENT;
    }
}
