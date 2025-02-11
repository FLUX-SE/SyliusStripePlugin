<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Transition\Checkout;

use Stripe\Checkout\Session;

final class SessionTransitionProvider implements SessionTransitionProviderInterface
{
    public function __construct(
        private SessionModeTransitionProviderInterface $sessionModeTransitionProvider,
    ) {
    }

    public function isAuthorize(Session $session): bool
    {
        if (Session::STATUS_COMPLETE !== $session->status) {
            return false;
        }

        return $this->sessionModeTransitionProvider->isAuthorize($session);
    }

    public function isComplete(Session $session): bool
    {
        if (Session::STATUS_COMPLETE !== $session->status) {
            return false;
        }

        return Session::PAYMENT_STATUS_UNPAID !== $session->payment_status;
    }

    public function isFail(Session $session): bool
    {
        return Session::STATUS_EXPIRED === $session->status;
    }

    public function isProcess(Session $session): bool
    {
        if (Session::STATUS_COMPLETE !== $session->status) {
            return false;
        }

        return Session::PAYMENT_STATUS_UNPAID === $session->payment_status;
    }

    public function isCancel(Session $session): bool
    {
        if (!$this->isProcess($session)) {
            return false;
        }

        return $this->sessionModeTransitionProvider->isCancel($session);
    }

    public function isRefund(Session $session): bool
    {
        if (!$this->isProcess($session)) {
            return false;
        }

        return $this->sessionModeTransitionProvider->isRefund($session);
    }
}
