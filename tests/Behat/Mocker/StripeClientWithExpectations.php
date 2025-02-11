<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Stripe\ApiResource;

/**
 * @implements StripeClientWithExpectationsInterface<ApiResource>
 */
final class StripeClientWithExpectations implements StripeClientWithExpectationsInterface
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function addExpectation(string $method, string $absUrl, array $body, bool $mergeParams = false): void
    {
        $cacheItem = $this->getCacheItem();
        $expectations = $this->getExpectations();
        $expectations[] = [
            'method' => $method,
            'absUrl' => $absUrl,
            'body' => $body,
            'mergeParams' => $mergeParams,
        ];
        $cacheItem->set($expectations);
        $this->cache->save($cacheItem);
    }

    public function resetExpectations(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    public function hasExpectations(): bool
    {
        return count($this->getExpectations()) > 0;
    }

    public function getExpectations(): array
    {
        /** @var array<array-key, array{
         *  method: string,
         *  absUrl: string,
         *  body: array<string, string|array<string, string>>,
         *  mergeParams: bool,
         * }> $expectations */
        $expectations = $this->getCacheItem()->get();

        return $expectations;
    }

    /**
     * @param array<array-key, mixed> $headers
     * @param array<array-key, mixed> $params
     *
     * @return array{0: string, 1: int, 2: array<string, string>}
     */
    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1')
    {
        $cacheItem = $this->getCacheItem();

        $expectations = $this->getExpectations();

        $expectation = array_shift($expectations);

        if (null === $expectation) {
            throw new \RuntimeException(sprintf(
                'No expectations found for this Stripe test request (method:%s absUrl:%s)',
                $method,
                $absUrl,
            ));
        }

        $expectedRequest = $expectation['method'] . ' ' . $expectation['absUrl'];
        $currentRequest = $method . ' ' . $absUrl;
        if ($expectedRequest !== $currentRequest) {
            throw new \RuntimeException(sprintf(
                'Expected request "%s" but got "%s"',
                $expectedRequest,
                $currentRequest,
            ));
        }

        $body = $expectation['body'];
        if ($expectation['mergeParams']) {
            $body = array_merge($expectation['body'], $params);
        }

        $cacheItem->set($expectations);
        $this->cache->save($cacheItem);

        return [
            json_encode($body, \JSON_THROW_ON_ERROR),
            200,
            [],
        ];
    }

    private function getCacheItem(): CacheItemInterface
    {
        if (false === $this->cache->hasItem(self::CACHE_KEY)) {
            $this->cache->getItem(self::CACHE_KEY)->set([]);
        }

        return $this->cache->getItem(self::CACHE_KEY);
    }
}
