<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Mockery\MockInterface;
use Stripe\Checkout\Session;
use Stripe\HttpClient\ClientInterface;

final class CheckoutSessionMocker
{
    public function __construct(
        private MockInterface&ClientInterface $mockClient,
    ) {
    }

    public function mockCreateAction(): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['post', Session::classUrl()])
            ->andReturnUsing(function ($method, $absUrl, $params) {
                return [
                    json_encode(array_merge([
                        'id' => 'cs_test_1',
                        'object' => Session::OBJECT_NAME,
                        'payment_intent' => 'pi_test_1',
                        'url' => 'https://checkout.stripe.com/c/pay/cs_1',
                    ], $params), JSON_THROW_ON_ERROR),
                    200,
                    []
                ];
            });
    }

    public function mockRetrieveAction(string $status, string $paymentStatus): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['get', \Mockery::pattern('#^'.Session::classUrl().'/cs_test_[^/]+$#')])
            ->andReturnUsing(function ($method, $absUrl) use ($status, $paymentStatus) {
                $id = str_replace(Session::classUrl().'/', '', $absUrl);
                return [
                    json_encode([
                        'id' => $id,
                        'object' => Session::OBJECT_NAME,
                        'status' => $status,
                        'payment_status' => $paymentStatus,
                        'payment_intent' => 'pi_test_1',
                    ], JSON_THROW_ON_ERROR),
                    200,
                    []
                ];
            });
    }

    public function mockAllAction(string $status): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['get', Session::classUrl()])
            ->andReturnUsing(function () use ($status) {
                return [
                    json_encode(['data' => [
                        [
                            'id' => 'cs_test_1',
                            'object' => Session::OBJECT_NAME,
                            'status' => $status,
                        ],
                    ]], JSON_THROW_ON_ERROR),
                    200,
                    []
                ];
            });
    }

    public function mockExpireAction(): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['get', \Mockery::pattern('#^'.Session::classUrl().'/cs_test_[^/]+/expire$#')])
            ->andReturnUsing(function ($method, $absUrl, $params) {
                $id = str_replace([Session::classUrl() . '/', '/expire'], '', $absUrl);
                return [
                    json_encode([
                    'id' => $id,
                    'object' => Session::OBJECT_NAME,
                    'status' => Session::STATUS_EXPIRED,
                ], JSON_THROW_ON_ERROR),
                    200,
                    []
                ];
            });
    }
}
