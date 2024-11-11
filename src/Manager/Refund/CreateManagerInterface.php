<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Refund;

use FluxSE\SyliusStripePlugin\Manager\CreateManagerInterface as BaseCreateManagerInterface;
use Stripe\Refund;

/**
 * @extends BaseCreateManagerInterface<Refund>
 */
interface CreateManagerInterface extends BaseCreateManagerInterface
{
}
