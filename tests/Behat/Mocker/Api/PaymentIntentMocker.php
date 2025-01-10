<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Mockery\MockInterface;
use Stripe\HttpClient\ClientInterface;
use Stripe\PaymentIntent;

final class PaymentIntentMocker
{
    public function __construct(
        private MockInterface&ClientInterface $mockClient,
    ) {
    }

    public function mockCreateAction(): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['post', PaymentIntent::classUrl()])
            ->andReturnUsing(function ($method, $absUrl, $params) {
                return [
                    json_encode(array_merge([
                        'id' => 'pi_test_1',
                        'object' => PaymentIntent::OBJECT_NAME,
                        'client_secret' => '1234567890',
                    ], $params), \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }

    public function mockRetrieveAction(string $status): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['get', \Mockery::pattern('#^' . PaymentIntent::classUrl() . '/pi_test_[^/]+$#')])
            ->andReturnUsing(function ($method, $absUrl) use ($status) {
                $id = str_replace(PaymentIntent::classUrl() . '/', '', $absUrl);

                return [
                    json_encode([
                        'id' => $id,
                        'object' => PaymentIntent::OBJECT_NAME,
                        'status' => $status,
                        'client_secret' => '1234567890',
                    ], \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }

    public function mockUpdateAction(string $status, string $captureMethod): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['post', \Mockery::pattern('#^' . PaymentIntent::classUrl() . '/pi_test_[^/]+$#')])
            ->andReturnUsing(function ($method, $absUrl, $params) use ($status, $captureMethod) {
                $id = str_replace(PaymentIntent::classUrl() . '/', '', $absUrl);

                return [
                    json_encode(array_merge([
                        'id' => $id,
                        'object' => PaymentIntent::OBJECT_NAME,
                        'status' => $status,
                        'capture_method' => $captureMethod,
                    ], $params), \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }

    public function mockCancelAction(string $captureMethod): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['post', \Mockery::pattern('#^' . PaymentIntent::classUrl() . '/pi_test_[^/]+/cancel$#')])
            ->andReturnUsing(function ($method, $absUrl) use ($captureMethod) {
                $id = str_replace([PaymentIntent::classUrl() . '/', '/cancel$'], '', $absUrl);

                return [
                    json_encode([
                        'id' => $id,
                        'object' => PaymentIntent::OBJECT_NAME,
                        'capture_method' => $captureMethod,
                        'status' => PaymentIntent::STATUS_CANCELED,
                    ], \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }

    public function mockCaptureAction(string $status): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['post', \Mockery::pattern('#^' . PaymentIntent::classUrl() . '/pi_test_[^/]+/capture#')])
            ->andReturnUsing(function ($method, $absUrl) use ($status) {
                $id = str_replace([PaymentIntent::classUrl() . '/', '/capture'], '', $absUrl);

                return [
                    json_encode([
                        'id' => $id,
                        'object' => PaymentIntent::OBJECT_NAME,
                        'status' => $status,
                        'capture_method' => PaymentIntent::CAPTURE_METHOD_MANUAL,
                    ], \JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            });
    }
}
