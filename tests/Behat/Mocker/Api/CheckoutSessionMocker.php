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

    /**
     * @param array<key-of<Session>, mixed> $data
     */
    public function mockCreateAction(array $data = []): void
    {
        $id = $data['id'] ?? 'cs_test_1';
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getCheckoutSessionBaseUrl(),
            array_merge($data, [
                'id' => $id,
                'object' => Session::OBJECT_NAME,
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_1',
                'status' => Session::STATUS_OPEN,
                'payment_status' => Session::PAYMENT_STATUS_UNPAID,
            ]),
            true,
        );
    }

    /**
     * @param array<key-of<Session>, mixed> $data
     */
    public function mockRetrieveAction(array $data = []): void
    {
        $id = $data['id'] ?? 'cs_test_1';
        $this->stripeClientWithExpectations->addExpectation(
            'get',
            $this->getCheckoutSessionBaseUrl() . '/' . $id,
            array_merge($data, [
                'id' => $id,
                'object' => Session::OBJECT_NAME,
            ]),
        );
    }

    /**
     * @param array<key-of<Session>, mixed> $data
     */
    public function mockExpireAction(array $data = []): void
    {
        $id = $data['id'] ?? 'cs_test_1';
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getCheckoutSessionBaseUrl() . '/' . $id . '/expire',
            array_merge_recursive($data, [
                'id' => $id,
                'object' => Session::OBJECT_NAME,
                'status' => Session::STATUS_EXPIRED,
                'payment_status' => Session::PAYMENT_STATUS_UNPAID,
            ]),
        );
    }

    private function getCheckoutSessionBaseUrl(): string
    {
        return Stripe::$apiBase . Session::classUrl();
    }
}
