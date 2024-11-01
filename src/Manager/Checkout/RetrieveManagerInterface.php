<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Checkout;

use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerInterface as BaseRetrieveManagerInterface;
use Stripe\Checkout\Session;

/**
 * @extends BaseRetrieveManagerInterface<Session>
 */
interface RetrieveManagerInterface extends BaseRetrieveManagerInterface
{
}
