<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface AfterUrlProviderInterface
{
    // Used for CheckoutSession creation
    public const SUCCESS_URL = 'success_url';

    public const CANCEL_URL = 'cancel_url';

    // Used for PaymentIntent form display
    public const ACTION_URL = 'action_url';

    public function getUrl(PaymentRequestInterface $paymentRequest, string $type): string;
}
