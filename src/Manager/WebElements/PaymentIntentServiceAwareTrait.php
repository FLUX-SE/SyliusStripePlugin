<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\WebElements;

use Stripe\Service\AbstractService;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;

trait PaymentIntentServiceAwareTrait
{
    /**
     * @return PaymentIntentService
     */
    private function getService(StripeClient $stripeClient): AbstractService
    {
        return $stripeClient->paymentIntents;
    }
}
