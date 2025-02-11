<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Transition\Checkout;

use Stripe\Checkout\Session;

interface SessionTransitionProviderInterface
{
    public function isAuthorize(Session $session): bool;

    public function isComplete(Session $session): bool;

    public function isFail(Session $session): bool;

    public function isProcess(Session $session): bool;

    public function isCancel(Session $session): bool;

    public function isRefund(Session $session): bool;
}
