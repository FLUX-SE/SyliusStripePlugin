<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker;

use Stripe\Charge;
use Stripe\Event;
use Stripe\PaymentIntent;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\EventMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\PaymentIntentMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api\RefundMocker;

final class StripeWebElementsMocker
{
    public function __construct(
        private PaymentIntentMocker $paymentIntentMocker,
        private RefundMocker $refundMocker,
        private EventMocker $eventMocker,
    ) {
    }

    public function mockCaptureOrAuthorize(): void
    {
        $this->paymentIntentMocker->mockCreateAction();
    }

    public function mockCancelPayment(string $captureMethod): void
    {
        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => PaymentIntent::STATUS_REQUIRES_CAPTURE,
            'capture_method' => $captureMethod,
        ]);
        $this->paymentIntentMocker->mockCancelAction($captureMethod);
    }

    public function mockRefundPayment(int $amount): void
    {
        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
            'amount' => $amount,
        ]);

        $this->refundMocker->mockCreateAction();
        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
            'latest_charge' => [
                'id' => 'ch_test_1',
                'object' => Charge::OBJECT_NAME,
                'refunded' => true,
            ],
        ]);
    }

    public function mockCompleteAuthorized(string $status, string $captureMethod): void
    {
        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => $status,
            'capture_method' => $captureMethod,
        ]);
        $this->paymentIntentMocker->mockCaptureAction(PaymentIntent::STATUS_SUCCEEDED);
    }

    public function mockGoBackPayment(): void
    {
        // CaptureEnd
        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
        ]);

        // CaptureEnd
        $this->mockCancelPayment(PaymentIntent::CAPTURE_METHOD_AUTOMATIC);

        // The Cancel workflow event is triggered
        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => PaymentIntent::STATUS_CANCELED,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
        ]);
    }

    public function mockSuccessfulPayment(): void
    {
        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
        ]);
    }

    public function mockAuthorizePayment(): void
    {
        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => PaymentIntent::STATUS_REQUIRES_CAPTURE,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_MANUAL,
        ]);
    }

    /**
     * @param array<key-of<Event>, mixed> $data
     */
    public function mockWebhookHandling(array $data): void
    {
        $this->eventMocker->mockRetrieveAction($data);
        /** @var array{object: array<key-of<PaymentIntent>, string>} $eventData */
        $eventData = $data['data'];
        $object = $eventData['object'];

        $this->paymentIntentMocker->mockRetrieveAction([
            'status' => $object['status'],
            'capture_method' => $object['capture_method'],
        ]);
    }
}
