<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\WebElements;

use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerInterface as BaseRetrieveManagerInterface;
use Stripe\PaymentIntent;

/**
 * @extends BaseRetrieveManagerInterface<PaymentIntent>
 */
interface RetrieveManagerInterface extends BaseRetrieveManagerInterface
{
}
