<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Event;

use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerInterface as BaseRetrieveManagerInterface;
use Stripe\Event;

/**
 * @extends BaseRetrieveManagerInterface<Event>
 */
interface RetrieveManagerInterface extends BaseRetrieveManagerInterface
{
}
