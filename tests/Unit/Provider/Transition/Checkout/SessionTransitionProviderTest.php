<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider\Transition\Checkout;

use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\SessionModeTransitionProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\SessionTransitionProvider;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;

final class SessionTransitionProviderTest extends TestCase
{
    private SessionModeTransitionProviderInterface $sessionModeTransitionProvider;
    private SessionTransitionProvider $sessionTransitionProvider;

    protected function setUp(): void
    {
        $this->sessionModeTransitionProvider = $this->createMock(SessionModeTransitionProviderInterface::class);
        $this->sessionTransitionProvider = new SessionTransitionProvider($this->sessionModeTransitionProvider);
    }

    public function test_it_returns_true_for_is_authorize_when_session_is_complete_and_mode_provider_returns_true(): void
    {
        $session = $this->createSession(Session::STATUS_COMPLETE);

        $this->sessionModeTransitionProvider
            ->expects($this->once())
            ->method('isAuthorize')
            ->with($session)
            ->willReturn(true);

        $result = $this->sessionTransitionProvider->isAuthorize($session);

        $this->assertTrue($result);
    }

    public function test_it_returns_false_for_is_authorize_when_session_is_not_complete(): void
    {
        $session = $this->createSession(Session::STATUS_OPEN);

        $this->sessionModeTransitionProvider
            ->expects($this->never())
            ->method('isAuthorize');

        $result = $this->sessionTransitionProvider->isAuthorize($session);

        $this->assertFalse($result);
    }

    public function test_it_returns_true_for_is_complete_when_session_is_complete_and_mode_provider_returns_true(): void
    {
        $session = $this->createSession(Session::STATUS_COMPLETE);

        $this->sessionModeTransitionProvider
            ->expects($this->once())
            ->method('isComplete')
            ->with($session)
            ->willReturn(true);

        $result = $this->sessionTransitionProvider->isComplete($session);

        $this->assertTrue($result);
    }

    public function test_it_returns_false_for_is_complete_when_session_is_not_complete(): void
    {
        $session = $this->createSession(Session::STATUS_OPEN);

        $this->sessionModeTransitionProvider
            ->expects($this->never())
            ->method('isComplete');

        $result = $this->sessionTransitionProvider->isComplete($session);

        $this->assertFalse($result);
    }

    public function test_it_returns_true_for_is_fail_when_session_is_expired(): void
    {
        $session = $this->createSession(Session::STATUS_EXPIRED);

        $this->sessionModeTransitionProvider
            ->expects($this->never())
            ->method('isFail');

        $result = $this->sessionTransitionProvider->isFail($session);

        $this->assertTrue($result);
    }

    public function test_it_delegates_to_mode_provider_for_is_fail_when_session_is_not_expired(): void
    {
        $session = $this->createSession(Session::STATUS_COMPLETE);

        $this->sessionModeTransitionProvider
            ->expects($this->once())
            ->method('isFail')
            ->with($session)
            ->willReturn(false);

        $result = $this->sessionTransitionProvider->isFail($session);

        $this->assertFalse($result);
    }

    public function test_it_returns_true_for_is_process_when_session_is_complete_and_mode_provider_returns_true(): void
    {
        $session = $this->createSession(Session::STATUS_COMPLETE);

        $this->sessionModeTransitionProvider
            ->expects($this->once())
            ->method('isProcess')
            ->with($session)
            ->willReturn(true);

        $result = $this->sessionTransitionProvider->isProcess($session);

        $this->assertTrue($result);
    }

    public function test_it_returns_false_for_is_process_when_session_is_not_complete(): void
    {
        $session = $this->createSession(Session::STATUS_OPEN);

        $this->sessionModeTransitionProvider
            ->expects($this->never())
            ->method('isProcess');

        $result = $this->sessionTransitionProvider->isProcess($session);

        $this->assertFalse($result);
    }

    public function test_it_returns_false_for_is_cancel_when_is_process_returns_false(): void
    {
        $session = $this->createSession(Session::STATUS_OPEN);

        $this->sessionModeTransitionProvider
            ->expects($this->never())
            ->method('isCancel');

        $result = $this->sessionTransitionProvider->isCancel($session);

        $this->assertFalse($result);
    }

    private function createSession(string $status): Session
    {
        return Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'status' => $status,
        ]);
    }
}

