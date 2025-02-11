<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Transition\Checkout;

use Stripe\Checkout\Session;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class CompositeSessionModeTransitionProvider implements SessionModeTransitionProviderInterface
{
    /**
     * @param ServiceProviderInterface<SessionModeTransitionProviderInterface> $serviceProvider
     */
    public function __construct(
        private ServiceProviderInterface $serviceProvider,
    ) {
    }

    public function isAuthorize(Session $session): bool
    {
        return $this->getSessionModeTransitionProvider($session)->isAuthorize($session);
    }

    public function isComplete(Session $session): bool
    {
        return $this->getSessionModeTransitionProvider($session)->isComplete($session);
    }

    public function isFail(Session $session): bool
    {
        return $this->getSessionModeTransitionProvider($session)->isFail($session);
    }

    public function isProcess(Session $session): bool
    {
        return $this->getSessionModeTransitionProvider($session)->isProcess($session);
    }

    public function isCancel(Session $session): bool
    {
        return $this->getSessionModeTransitionProvider($session)->isCancel($session);
    }

    public function isRefund(Session $session): bool
    {
        return $this->getSessionModeTransitionProvider($session)->isRefund($session);
    }

    private function getSessionModeTransitionProvider(Session $session): SessionModeTransitionProviderInterface
    {
        $sessionModeTransitionProvider = $this->serviceProvider->get($session->mode);

        if ($sessionModeTransitionProvider::getSupportedMode() !== $session->mode) {
            throw new \InvalidArgumentException(sprintf(
                'The SessionModeTransitionProvider support mode "%s", "%s" given.',
                $sessionModeTransitionProvider::getSupportedMode(),
                $session->mode,
            ));
        }

        return $sessionModeTransitionProvider;
    }

    public static function getSupportedMode(): string
    {
        throw new \RuntimeException('This method is not meant to be called!');
    }
}
