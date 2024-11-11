<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Mockery\MockInterface;
use Stripe\HttpClient\ClientInterface;
use Stripe\Refund;

final class RefundMocker
{
    public function __construct(
        private MockInterface&ClientInterface $mockClient,
    ) {
    }

    public function mockCreateAction(): void
    {
        $this->mockClient
            ->expects('request')
            ->withArgs(['post', Refund::classUrl()])
            ->andReturnUsing(function ($method, $absUrl, $params) {
                return [
                    json_encode(array_merge([
                        'id' => 're_1',
                        'object' => Refund::OBJECT_NAME,
                    ], $params), JSON_THROW_ON_ERROR),
                    200,
                    []
                ];
            });
    }
}
