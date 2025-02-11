<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker;

use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\CheckoutSessionMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\EventMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\PaymentIntentMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\RefundMocker;

class StripeCheckoutMocker
{
    public function __construct(
        private CheckoutSessionMocker $checkoutSessionMocker,
        private PaymentIntentMocker $paymentIntentMocker,
        private RefundMocker $refundMocker,
        private EventMocker $eventMocker,
    ) {
    }

    public function mockCaptureOrAuthorize(): void
    {
        $this->checkoutSessionMocker->mockCreateAction();
    }

    public function mockCancelPayment(string $status, string $captureMethod): void
    {
        $this->paymentIntentMocker->mockRetrieveAction($status, $captureMethod);
        $this->paymentIntentMocker->mockCancelAction($captureMethod);
        $this->paymentIntentMocker->mockRetrieveAction(PaymentIntent::STATUS_CANCELED, $captureMethod);
    }

    public function mockRefundPayment(): void
    {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'payment_intent' => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_SUCCEEDED,
                'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
            ],
        ]);
        $this->refundMocker->mockCreateAction();
    }

    public function mockExpirePayment(string $status, string $paymentStatus): void
    {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_intent' => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_SUCCEEDED,
            ],
        ]);
        $this->checkoutSessionMocker->mockExpireAction();
    }

    public function mockCompleteAuthorized(string $status, string $captureMethod): void
    {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'payment_intent' => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => $status,
                'capture_method' => $captureMethod,
            ],
        ]);

        $this->paymentIntentMocker->mockCaptureAction(PaymentIntent::STATUS_SUCCEEDED);
    }

    public function mockGoBackPayment(): void
    {
        // Capture End retrieval
        $this->mockRetrieveSession(
            Session::STATUS_OPEN,
            Session::PAYMENT_STATUS_UNPAID,
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
        );

        $this->mockExpireSession();
    }

    public function mockSuccessfulPayment(): void
    {
        // Capture End retrieval
        $this->mockRetrieveSession(
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_PAID,
            PaymentIntent::STATUS_SUCCEEDED,
            PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
        );
    }

    public function mockAuthorizePayment(): void
    {
        // Capture End retrieval
        $this->mockRetrieveSession(
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_UNPAID,
            PaymentIntent::STATUS_REQUIRES_CAPTURE,
            PaymentIntent::CAPTURE_METHOD_MANUAL,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $paymentIntentData
     */
    public function mockWebhookHandling(array $data, array $paymentIntentData): void
    {
        $this->eventMocker->mockRetrieveAction($data);
        $this->checkoutSessionMocker->mockRetrieveAction($paymentIntentData);
    }

    public function mockRetrieveSession(
        string $sessionStatus,
        string $paymentStatus,
        string $paymentIntentStatus,
        string $paymentIntentCaptureMethod,
    ): void {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'status' => $sessionStatus,
            'payment_status' => $paymentStatus,
            'payment_intent' => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => $paymentIntentStatus,
                'capture_method' => $paymentIntentCaptureMethod,
            ],
        ]);
    }

    public function mockExpireSession(): void
    {
        $this->checkoutSessionMocker->mockExpireAction();
    }
}
