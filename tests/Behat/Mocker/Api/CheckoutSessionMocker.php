<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Mockery\MockInterface;
use Stripe\Checkout\Session;
use Stripe\HttpClient\ClientInterface;
use Stripe\Stripe;

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
            ->withSomeOfArgs('post', $this->getCheckoutSessionBaseUrl())
            ->andReturnUsing(function ($method, $absUrl, $params) {
                return [
                    json_encode(array_merge([
                        'id' => 'cs_test_1',
                        'object' => Session::OBJECT_NAME,
                        'payment_intent' => 'pi_test_1',
                        'url' => 'https://checkout.stripe.com/c/pay/cs_test_1',
                        'status' => Session::STATUS_OPEN,
                        'payment_status' => Session::PAYMENT_STATUS_UNPAID,
                    ], $params), \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }

    public function mockRetrieveAction(string $status, string $paymentStatus): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(function ($method, $url) {
                if ('get' !== $method) {
                    return false;
                }
                if (false === preg_match('#'. $this->getCheckoutSessionBaseUrl() .'/[^/]+$#', $url)) {
                    return false;
                }
                return true;
            })
            ->andReturnUsing(function ($method, $absUrl) use ($status, $paymentStatus) {
                $id = str_replace($this->getCheckoutSessionBaseUrl() . '/', '', $absUrl);

                return [
                    json_encode([
                        'id' => $id,
                        'object' => Session::OBJECT_NAME,
                        'status' => $status,
                        'payment_status' => $paymentStatus,
                        'payment_intent' => 'pi_test_1',
                    ], \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }

    public function mockAllAction(string $status): void
    {
        $this->mockClient
            ->expects('request')
            ->with('get', $this->getCheckoutSessionBaseUrl())
            ->andReturnUsing(function () use ($status) {
                return [
                    json_encode(['data' => [
                        [
                            'id' => 'cs_test_1',
                            'object' => Session::OBJECT_NAME,
                            'status' => $status,
                        ],
                    ]], \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }

    public function mockExpireAction(): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(function ($method, $url) {
                if ('post' !== $method) {
                    return false;
                }
                if (false === preg_match('#'. $this->getCheckoutSessionBaseUrl() .'/[^/]+/expire$#', $url)) {
                    return false;
                }
                return true;
            })
            ->andReturnUsing(function ($method, $absUrl) {
                $id = str_replace([$this->getCheckoutSessionBaseUrl() . '/', '/expire'], '', $absUrl);

                return [
                    json_encode([
                    'id' => $id,
                    'object' => Session::OBJECT_NAME,
                    'status' => Session::STATUS_EXPIRED,
                    'payment_status' => Session::PAYMENT_STATUS_UNPAID,
                ], \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }

    private function getCheckoutSessionBaseUrl(): string
    {
        return Stripe::$apiBase.Session::classUrl();
    }
}
