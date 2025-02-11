<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker;

use Stripe\ApiResource;
use Stripe\HttpClient\ClientInterface;

/**
 * @template T as ApiResource
 */
interface StripeClientWithExpectationsInterface extends ClientInterface
{
    public const CACHE_KEY = 'stripe_client_expectations';

    /**
     * @param array<key-of<T>, mixed> $body
     */
    public function addExpectation(string $method, string $absUrl, array $body, bool $mergeParams = false): void;

    public function hasExpectations(): bool;

    /**
     * @return array<array-key, array{
     *      method: string,
     *      absUrl: string,
     *      body: array<string, string|array<string, string>>,
     *      mergeParams: bool,
     *  }>
     */
    public function getExpectations(): array;

    public function resetExpectations(): void;
}
