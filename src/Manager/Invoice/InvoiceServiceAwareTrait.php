<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Invoice;

use Stripe\Service\AbstractService;
use Stripe\Service\InvoiceService;
use Stripe\StripeClient;

trait InvoiceServiceAwareTrait
{
    /**
     * @return InvoiceService
     */
    private function getService(StripeClient $stripeClient): AbstractService
    {
        return $stripeClient->invoices;
    }
}
