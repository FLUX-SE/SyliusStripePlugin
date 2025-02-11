<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Stripe\Refund;
use Stripe\Stripe;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

class RefundMocker
{
    /**
     * @param StripeClientWithExpectationsInterface<Refund> $stripeClientWithExpectations
     */
    public function __construct(
        private StripeClientWithExpectationsInterface $stripeClientWithExpectations,
    ) {
    }

    public function mockCreateAction(): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getRefundBaseUrl(),
            [
                'id' => 're_1',
                'object' => Refund::OBJECT_NAME,
            ],
            true,
        );
    }

    private function getRefundBaseUrl(): string
    {
        return Stripe::$apiBase . Refund::classUrl();
    }
}
