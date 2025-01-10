<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker;

use Mockery\MockInterface;
use Stripe\Checkout\Session;
use Stripe\HttpClient\ClientInterface;
use Stripe\PaymentIntent;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\CheckoutSessionMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\PaymentIntentMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\RefundMocker;

final class StripeCheckoutMocker
{
    public function __construct(
        private MockInterface&ClientInterface $mockClient,
        private CheckoutSessionMocker $checkoutSessionMocker,
        private PaymentIntentMocker $paymentIntentMocker,
        private RefundMocker $refundMocker,
    ) {
    }

    public function mockCaptureOrAuthorize(callable $action): void
    {
        $this->mockClient->expects([]);

        $this->checkoutSessionMocker->mockCreateAction();

        $this->mockSessionSync(
            $action,
            Session::STATUS_OPEN,
            Session::PAYMENT_STATUS_UNPAID,
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
        );
    }

    public function mockCancelPayment(string $status, string $captureMethod): void
    {
        $this->mockClient->expects([]);

        $this->checkoutSessionMocker->mockRetrieveAction(
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_PAID,
        );
        $this->checkoutSessionMocker->mockExpireAction();
    }

    public function mockRefundPayment(): void
    {
        $this->mockClient->expects([]);

        $this->refundMocker->mockCreateAction();
    }

    public function mockExpirePayment(string $status, string $paymentStatus): void
    {
        $this->mockClient->expects([]);

        $this->checkoutSessionMocker->mockRetrieveAction(
            $status,
            $paymentStatus,
        );

        if ($status !== Session::STATUS_OPEN) {
            return;
        }

        $this->checkoutSessionMocker->mockExpireAction();
    }

    public function mockCaptureAuthorization(string $status, string $captureMethod): void
    {
        $this->mockClient->expects([]);

        $this->paymentIntentMocker->mockUpdateAction($status, $captureMethod);
        $this->paymentIntentMocker->mockCaptureAction(PaymentIntent::STATUS_SUCCEEDED);
        $this->paymentIntentMocker->mockRetrieveAction(PaymentIntent::STATUS_SUCCEEDED);
    }

    public function mockGoBackPayment(callable $action): void
    {
        $this->mockExpireSession(Session::STATUS_OPEN);
        $this->mockSessionSync(
            $action,
            Session::STATUS_OPEN,
            Session::PAYMENT_STATUS_UNPAID,
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
        );
    }

    public function mockSuccessfulPayment(callable $notifyAction, callable $action): void
    {
        $this->mockSessionSync(
            $notifyAction,
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_PAID,
            PaymentIntent::STATUS_SUCCEEDED,
        );
        $this->mockPaymentIntentSync($action, PaymentIntent::STATUS_SUCCEEDED);
    }

    public function mockAuthorizePayment(callable $notifyAction, callable $action): void
    {
        $this->mockSessionSync(
            $notifyAction,
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_UNPAID,
            PaymentIntent::STATUS_REQUIRES_CAPTURE,
        );
        $this->mockPaymentIntentSync($action, PaymentIntent::STATUS_REQUIRES_CAPTURE);
    }

    public function mockSuccessfulPaymentWithoutWebhook(callable $action): void
    {
        $this->mockSessionSync(
            $action,
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_PAID,
            PaymentIntent::STATUS_SUCCEEDED,
        );
    }

    public function mockSuccessfulPaymentWithoutWebhookUsingAuthorize(callable $action): void
    {
        $this->mockSessionSync(
            $action,
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_UNPAID,
            PaymentIntent::STATUS_REQUIRES_CAPTURE,
        );
    }

    public function mockPaymentIntentSync(callable $action, string $status): void
    {
        $this->paymentIntentMocker->mockRetrieveAction($status);

        $action();

        $this->mockClient->expects([]);
    }

    public function mockSessionSync(
        callable $action,
        string $sessionStatus,
        string $paymentStatus,
        string $paymentIntentStatus,
    ): void {
        $this->checkoutSessionMocker->mockRetrieveAction($sessionStatus, $paymentStatus);
        $this->mockPaymentIntentSync($action, $paymentIntentStatus);
    }

    public function mockExpireSession(string $sessionStatus): void
    {
        $this->checkoutSessionMocker->mockAllAction($sessionStatus);
        $this->checkoutSessionMocker->mockExpireAction();
    }
}
