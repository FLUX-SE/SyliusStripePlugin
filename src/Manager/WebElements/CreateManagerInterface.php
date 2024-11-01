<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\WebElements;

use FluxSE\SyliusStripePlugin\Manager\CreateManagerInterface as BaseCreateManagerInterface;
use Stripe\PaymentIntent;

/**
 * @extends BaseCreateManagerInterface<PaymentIntent>
 */
interface CreateManagerInterface extends BaseCreateManagerInterface
{
}
