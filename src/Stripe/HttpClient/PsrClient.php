<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\HttpClient;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Stripe\Exception\ApiConnectionException;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\StreamingClientInterface;
use Stripe\Util\Util;

final class PsrClient implements ClientInterface, StreamingClientInterface
{
    public function __construct(
        private PsrClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private int $chunkSize = 8192,
    ) {
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1'): array
    {
        $request = $this->constructRequest($method, $absUrl, $headers, $params, $hasFile);

        $response = $this->sendRequest($request);

        return $this->prepareReturnedResponse($response);
    }

    public function requestStream($method, $absUrl, $headers, $params, $hasFile, $readBodyChunkCallable): array
    {
        $request = $this->constructRequest($method, $absUrl, $headers, $params, $hasFile);

        $response = $this->sendRequest($request);

        $body = $response->getBody();
        $body->rewind();
        while (false === $body->eof()) {
            $buf = $body->read($this->chunkSize);
            $readBodyChunkCallable($buf);
        }

        return $this->prepareReturnedResponse($response);
    }

    /**
     * @param string[] $headers
     */
    private function constructRequest(string $method, string $absUrl, array $headers, array $params, bool $hasFile): RequestInterface
    {
        $params = Util::objectsToIds($params);

        $request = $this->requestFactory->createRequest(strtoupper($method), $absUrl);

        $encodeParameters = Util::encodeParameters($params);
        if ('post' === $method) {
            $bodyStream = $this->streamFactory->createStream($encodeParameters);
            $request = $request->withBody($bodyStream);
        } else {
            $uri = $request->getUri()->withQuery($encodeParameters);
            $request = $request->withUri($uri);
        }

        foreach ($headers as $header) {
            $headerValue = preg_replace('#^[^:]+:\s*#', '', $header);
            $headerName = preg_replace('#:.+$#', '', $header);
            $request = $request->withHeader($headerName, $headerValue);
        }

        // @todo see if this is still necessary
        // It is only safe to retry network failures on POST requests if we add an Idempotency-Key header
        /*if (('post' === $method) && (Stripe::$maxNetworkRetries > 0) && !$request->hasHeader('Idempotency-Key')) {
            $request = $request->withHeader('Idempotency-Key', $this->randomGenerator->uuid());
        }*/

        return $request;
    }

    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ApiConnectionException(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }

        return $response;
    }

    private function prepareReturnedResponse(ResponseInterface $response): array
    {
        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $responseHeaders[$name] = $value;
            }
        }

        return [
            $response->getBody()->__toString(),
            $response->getStatusCode(),
            $responseHeaders,
        ];
    }
}
