<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface AfterUrlProviderInterface
{
    // Used for CheckoutSession creation
    const SUCCESS_URL = 'success_url';
    const CANCEL_URL = 'cancel_url';

    // Used for PaymentIntent form display
    const ACTION_URL = 'action_url';

    public function getUrl(PaymentRequestInterface $paymentRequest, string $type): string;
}
