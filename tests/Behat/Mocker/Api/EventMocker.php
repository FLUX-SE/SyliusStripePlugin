<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Stripe\Event;
use Stripe\Stripe;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

class EventMocker
{
    /**
     * @param StripeClientWithExpectationsInterface<Event> $stripeClientWithExpectations
     */
    public function __construct(
        private StripeClientWithExpectationsInterface $stripeClientWithExpectations,
    ) {
    }

    /**
     * @param array<key-of<Event>, mixed> $data
     */
    public function mockRetrieveAction(array $data): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'get',
            $this->getEventBaseUrl() . '/evt_test_1',
            array_merge($data, [
                'id' => 'evt_test_1',
                'object' => Event::OBJECT_NAME,
            ]),
        );
    }

    private function getEventBaseUrl(): string
    {
        return Stripe::$apiBase . Event::classUrl();
    }
}
