<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker;

use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Subscription;
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
        $this->checkoutSessionMocker->mockCreateAction([
            'mode' => Session::MODE_PAYMENT,
            PaymentIntent::OBJECT_NAME => 'pi_test_1',
        ]);
    }

    public function mockCancelPayment(string $status, string $captureMethod): void
    {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_PAYMENT,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            PaymentIntent::OBJECT_NAME => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => $status,
                'capture_method' => $captureMethod,
            ],
        ]);
        $this->paymentIntentMocker->mockCancelAction($captureMethod);
    }

    public function mockRefundPayment(int $amount): void
    {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_PAYMENT,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'amount_total' => $amount,
            PaymentIntent::OBJECT_NAME => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_SUCCEEDED,
                'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
            ],
        ]);
        $this->refundMocker->mockCreateAction();
        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_PAYMENT,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'amount_total' => $amount,
            PaymentIntent::OBJECT_NAME => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_SUCCEEDED,
                'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
                'latest_charge' => [
                    'id' => 'ch_test_1',
                    'object' => Charge::OBJECT_NAME,
                    'refunded' => true,
                ],
            ],
        ]);
    }

    public function mockRefundSubscription(int $amount): void
    {
        $subscriptionData = [
            'id' => 'sub_test_1',
            'object' => Subscription::OBJECT_NAME,
            'status' => Subscription::STATUS_ACTIVE,
        ];

        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_SUBSCRIPTION,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'amount_total' => $amount,
            Subscription::OBJECT_NAME => $subscriptionData,
            Invoice::OBJECT_NAME => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
                PaymentIntent::OBJECT_NAME => [
                    'id' => 'pi_test_1',
                    'object' => PaymentIntent::OBJECT_NAME,
                ],
            ],
        ]);

        $this->refundMocker->mockCreateAction();

        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_SUBSCRIPTION,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'amount_total' => $amount,
            Subscription::OBJECT_NAME => $subscriptionData,
            Invoice::OBJECT_NAME => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
                PaymentIntent::OBJECT_NAME => [
                    'id' => 'pi_test_1',
                    'object' => PaymentIntent::OBJECT_NAME,
                    'latest_charge' => [
                        'id' => 'ch_test_1',
                        'object' => Charge::OBJECT_NAME,
                        'refunded' => true,
                    ],
                ],
            ],
        ]);
    }

    public function mockExpirePayment(string $status, string $paymentStatus): void
    {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_PAYMENT,
            'status' => $status,
            'payment_status' => $paymentStatus,
            PaymentIntent::OBJECT_NAME => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_SUCCEEDED,
            ],
        ]);
        $this->checkoutSessionMocker->mockExpireAction();
    }

    public function mockExpireSubscription(string $status, string $paymentStatus): void
    {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_SUBSCRIPTION,
            'status' => $status,
            'payment_status' => $paymentStatus,
            Subscription::OBJECT_NAME => null,
        ]);
        $this->checkoutSessionMocker->mockExpireAction();
    }

    public function mockCompleteAuthorized(string $status, string $captureMethod): void
    {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_PAYMENT,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            PaymentIntent::OBJECT_NAME => [
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
        $this->mockRetrieveSessionPayment(
            Session::STATUS_OPEN,
            Session::PAYMENT_STATUS_UNPAID,
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
        );

        $this->mockExpireSession();
    }

    public function mockGoBackSubscription(): void
    {
        // Capture End retrieval
        $this->mockRetrieveSessionSubscription(
            Session::STATUS_OPEN,
            Session::PAYMENT_STATUS_UNPAID,
            null,
        );

        $this->mockExpireSession();
    }

    public function mockSuccessfulPayment(): void
    {
        // Capture End retrieval
        $this->mockRetrieveSessionPayment(
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_PAID,
            PaymentIntent::STATUS_SUCCEEDED,
            PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
        );
    }

    public function mockSuccessfulSubscription(): void
    {
        // Capture End retrieval
        $this->mockRetrieveSessionSubscription(
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_PAID,
            Subscription::STATUS_ACTIVE,
        );
    }

    public function mockAuthorizePayment(): void
    {
        // Capture End retrieval
        $this->mockRetrieveSessionPayment(
            Session::STATUS_COMPLETE,
            Session::PAYMENT_STATUS_UNPAID,
            PaymentIntent::STATUS_REQUIRES_CAPTURE,
            PaymentIntent::CAPTURE_METHOD_MANUAL,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $checkoutSessionData
     */
    public function mockWebhookHandling(array $data, array $checkoutSessionData): void
    {
        $this->eventMocker->mockRetrieveAction($data);
        $this->checkoutSessionMocker->mockRetrieveAction($checkoutSessionData);
    }

    public function mockRetrieveSessionPayment(
        string $sessionStatus,
        string $paymentStatus,
        string $paymentIntentStatus,
        string $paymentIntentCaptureMethod,
    ): void {
        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_PAYMENT,
            'status' => $sessionStatus,
            'payment_status' => $paymentStatus,
            PaymentIntent::OBJECT_NAME => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => $paymentIntentStatus,
                'capture_method' => $paymentIntentCaptureMethod,
            ],
        ]);
    }

    public function mockRetrieveSessionSubscription(
        string $sessionStatus,
        string $paymentStatus,
        ?string $subscriptionStatus,
    ): void {
        $subscriptionData = $subscriptionStatus === null ? null : [
            'id' => 'sub_test_1',
            'object' => Subscription::OBJECT_NAME,
            'status' => $subscriptionStatus,
        ];

        $this->checkoutSessionMocker->mockRetrieveAction([
            'mode' => Session::MODE_PAYMENT,
            'status' => $sessionStatus,
            'payment_status' => $paymentStatus,
            Subscription::OBJECT_NAME => $subscriptionData,
        ]);
    }

    public function mockExpireSession(): void
    {
        $this->checkoutSessionMocker->mockExpireAction();
    }
}
