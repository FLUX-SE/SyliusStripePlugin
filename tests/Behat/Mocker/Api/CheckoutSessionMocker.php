<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Stripe\Checkout\Session;
use Stripe\Stripe;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

class CheckoutSessionMocker
{
    /**
     * @param StripeClientWithExpectationsInterface<Session> $stripeClientWithExpectations
     */
    public function __construct(
        private StripeClientWithExpectationsInterface $stripeClientWithExpectations,
    ) {
    }

    public function mockCreateAction(): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getCheckoutSessionBaseUrl(),
            [
                'id' => 'cs_test_1',
                'object' => Session::OBJECT_NAME,
                'mode' => Session::MODE_PAYMENT,
                'payment_intent' => 'pi_test_1',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_1',
                'status' => Session::STATUS_OPEN,
                'payment_status' => Session::PAYMENT_STATUS_UNPAID,
            ],
            true,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function mockRetrieveAction(array $data): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'get',
            $this->getCheckoutSessionBaseUrl() . '/cs_test_1',
            array_merge($data, [
                'id' => 'cs_test_1',
                'object' => Session::OBJECT_NAME,
                'mode' => Session::MODE_PAYMENT,
            ]),
        );
    }

    public function mockAllAction(string $status): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'get',
            $this->getCheckoutSessionBaseUrl(),
            [
                'data' => [
                    [
                        'id' => 'cs_test_1',
                        'object' => Session::OBJECT_NAME,
                        'status' => $status,
                        'mode' => Session::MODE_PAYMENT,
                    ],
                ],
            ],
        );
    }

    public function mockExpireAction(): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getCheckoutSessionBaseUrl() . '/cs_test_1/expire',
            [
                'id' => 'cs_test_1',
                'object' => Session::OBJECT_NAME,
                'status' => Session::STATUS_EXPIRED,
                'payment_status' => Session::PAYMENT_STATUS_UNPAID,
            ],
        );
    }

    private function getCheckoutSessionBaseUrl(): string
    {
        return Stripe::$apiBase . Session::classUrl();
    }
}
