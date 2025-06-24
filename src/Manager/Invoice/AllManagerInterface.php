<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Invoice;

use FluxSE\SyliusStripePlugin\Manager\AllManagerInterface as BaseAllManagerInterface;
use Stripe\Refund;

/**
 * @extends BaseAllManagerInterface<Refund>
 */
interface AllManagerInterface extends BaseAllManagerInterface
{
}
