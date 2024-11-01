<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Checkout;

use FluxSE\SyliusStripePlugin\Manager\CreateManagerInterface as BaseCreateManagerInterface;
use Stripe\Checkout\Session;

/**
 * @extends BaseCreateManagerInterface<Session>
 */
interface CreateManagerInterface extends BaseCreateManagerInterface
{
}
