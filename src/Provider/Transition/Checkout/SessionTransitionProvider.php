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

        return $this->sessionModeTransitionProvider->isComplete($session);
    }

    public function isFail(Session $session): bool
    {
        if (Session::STATUS_EXPIRED === $session->status) {
            return true;
        }

        return $this->sessionModeTransitionProvider->isFail($session);
    }

    public function isProcess(Session $session): bool
    {
        if (Session::STATUS_COMPLETE !== $session->status) {
            return false;
        }

        return $this->sessionModeTransitionProvider->isProcess($session);
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
