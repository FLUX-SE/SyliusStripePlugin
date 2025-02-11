<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Stripe\PaymentIntent;
use Stripe\Stripe;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

class PaymentIntentMocker
{
    /**
     * @param StripeClientWithExpectationsInterface<PaymentIntent> $stripeClientWithExpectations
     */
    public function __construct(
        private StripeClientWithExpectationsInterface $stripeClientWithExpectations,
    ) {
    }

    public function mockCreateAction(): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getPaymentIntentBaseUrl(),
            [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
                'client_secret' => '1234567890',
            ],
            true,
        );
    }

    public function mockRetrieveAction(string $status, string $captureMethod): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'get',
            $this->getPaymentIntentBaseUrl() . '/pi_test_1',
            [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => $status,
                'capture_method' => $captureMethod,
                'client_secret' => '1234567890',
            ],
        );
    }

    public function mockUpdateAction(string $status, string $captureMethod): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getPaymentIntentBaseUrl() . '/pi_test_1',
            [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => $status,
                'capture_method' => $captureMethod,
                'client_secret' => '1234567890',
            ],
            true,
        );
    }

    public function mockCancelAction(string $captureMethod): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getPaymentIntentBaseUrl() . '/pi_test_1/cancel',
            [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'capture_method' => $captureMethod,
                'status' => PaymentIntent::STATUS_CANCELED,
            ],
        );
    }

    public function mockCaptureAction(string $status): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getPaymentIntentBaseUrl() . '/pi_test_1/capture',
            [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => $status,
                'capture_method' => PaymentIntent::CAPTURE_METHOD_MANUAL,
            ],
        );
    }

    private function getPaymentIntentBaseUrl(): string
    {
        return Stripe::$apiBase . PaymentIntent::classUrl();
    }
}
