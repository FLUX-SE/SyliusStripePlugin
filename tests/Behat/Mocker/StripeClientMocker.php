<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker;

use Mockery;
use Mockery\MockInterface;
use Stripe\HttpClient\ClientInterface;

final class StripeClientMocker
{
    /**
     * @param class-string<ClientInterface> $className
     */
    public function __invoke(string $className): MockInterface
    {
        $mock = Mockery::fetchMock('stripe_client');

        return $mock ?? Mockery::namedMock('stripe_client', $className);
    }
}
