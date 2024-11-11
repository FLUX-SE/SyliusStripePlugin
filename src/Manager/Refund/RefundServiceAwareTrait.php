<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Refund;

use Stripe\Service\AbstractService;
use Stripe\Service\RefundService;
use Stripe\StripeClient;

trait RefundServiceAwareTrait
{
    /**
     * @return RefundService
     */
    private function getService(StripeClient $stripeClient): AbstractService
    {
        return $stripeClient->refunds;
    }
}
