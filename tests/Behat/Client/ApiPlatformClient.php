<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Client;

use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\RequestInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * @todo remove this class when Sylius get to the next release
 */
final class ApiPlatformClient implements ApiClientInterface
{
    public function __construct(
        private ApiClientInterface $client,
    ) {
    }

    public function request(?RequestInterface $request = null, bool $forgetResponse = false): Response
    {
        return $this->client->request($request, $forgetResponse);
    }

    public function index(string $resource, array $queryParameters = [], bool $forgetResponse = false): Response
    {
        return $this->client->index($resource, $queryParameters, $forgetResponse);
    }

    public function showByIri(string $iri, bool $forgetResponse = false): Response
    {
        return $this->client->showByIri($iri, $forgetResponse);
    }

    public function subResourceIndex(string $resource, string $subResource, string $id, array $queryParameters = [], bool $forgetResponse = false): Response
    {
        return $this->client->subResourceIndex($resource, $subResource, $id, $queryParameters, $forgetResponse);
    }

    public function show(string $resource, string $id, bool $forgetResponse = false): Response
    {
        return $this->client->show($resource, $id, $forgetResponse);
    }

    public function create(?RequestInterface $request = null, bool $forgetResponse = false): Response
    {
        return $this->client->create($request, $forgetResponse);
    }

    public function update(bool $forgetResponse = false): Response
    {
        return $this->client->update($forgetResponse);
    }

    public function delete(string $resource, string $id, bool $forgetResponse = false): Response
    {
        return $this->client->delete($resource, $id, $forgetResponse);
    }

    public function filter(): Response
    {
        return $this->client->filter();
    }

    public function sort(array $sorting): Response
    {
        return $this->client->sort($sorting);
    }

    public function applyTransition(string $resource, string $id, string $transition, array $content = []): Response
    {
        return $this->client->applyTransition($resource, $id, $transition, $content);
    }

    public function customItemAction(string $resource, string $id, string $type, string $action): Response
    {
        return $this->client->customItemAction($resource, $id, $type, $action);
    }

    public function customAction(string $url, string $method): Response
    {
        return $this->client->customAction($url, $method);
    }

    public function resend(): Response
    {
        return $this->client->resend();
    }

    public function executeCustomRequest(RequestInterface $request): Response
    {
        $content = $request->getContent();
        if (isset($content['action']) && $content['action'] === PaymentRequestInterface::ACTION_CAPTURE) {
            unset($content['action']);
            /** @var string|null $paymentMethod */
            $paymentMethod = $content['paymentMethodCode'] ?? null;
            if (null !== $paymentMethod) {
                $content['paymentMethodCode'] = preg_replace(
                    '#^(/api/v2/shop/payment-methods/)(.+)$#',
                    '$2',
                    $paymentMethod,
                );
            }
            $request->setContent($content);
        }

        return $this->client->executeCustomRequest($request);
    }

    public function buildCreateRequest(string $url): ApiClientInterface
    {
        return $this->client->buildCreateRequest($url);
    }

    public function buildUpdateRequest(string $uri, ?string $id = null): ApiClientInterface
    {
        return $this->client->buildUpdateRequest($uri, $id);
    }

    public function buildCustomUpdateRequest(string $uri, ?string $id = null): ApiClientInterface
    {
        return $this->client->buildCustomUpdateRequest($uri, $id);
    }

    public function setRequestData(array $data): ApiClientInterface
    {
        return $this->client->setRequestData($data);
    }

    public function addParameter(string $key, bool|int|string $value): ApiClientInterface
    {
        return $this->client->addParameter($key, $value);
    }

    public function addFilter(string $key, bool|int|string $value): void
    {
        $this->client->addFilter($key, $value);
    }

    public function clearParameters(): void
    {
        $this->client->clearParameters();
    }

    public function addFile(string $key, UploadedFile $file): void
    {
        $this->client->addFile($key, $file);
    }

    public function addRequestData(string $key, array|bool|int|string|null $value): ApiClientInterface
    {
        return $this->client->addRequestData($key, $value);
    }

    public function replaceRequestData(string $key, array|bool|int|string|null $value): void
    {
        $this->client->replaceRequestData($key, $value);
    }

    public function setSubResourceData(string $key, array $data): void
    {
        $this->client->setSubResourceData($key, $data);
    }

    public function addSubResourceData(string $key, array $data): void
    {
        $this->client->addSubResourceData($key, $data);
    }

    public function removeSubResourceIri(string $subResourceKey, string $iri): void
    {
        $this->client->removeSubResourceIri($subResourceKey, $iri);
    }

    public function removeSubResourceObject(string $subResourceKey, string $value, string $key = '@id'): void
    {
        $this->client->removeSubResourceObject($subResourceKey, $value, $key);
    }

    public function updateRequestData(array $data): void
    {
        $this->client->updateRequestData($data);
    }

    public function getContent(): array
    {
        return $this->client->getContent();
    }

    public function getLastResponse(): Response
    {
        return $this->client->getLastResponse();
    }

    public function getToken(): ?string
    {
        return $this->client->getToken();
    }

    public function requestGet(string $uri, array $queryParameters = [], array $headers = []): Response
    {
        if (str_starts_with($uri, '/api/v2/shop/orders/cart/payments/')) {
            $uri = preg_replace('#^(/api/v2/shop/)(orders/.+)$#', '$2', $uri) ?? $uri;
        }

        return $this->client->requestGet($uri, $queryParameters, $headers);
    }

    public function requestPatch(string $uri, array $body = [], array $queryParameters = [], array $headers = []): Response
    {
        return $this->client->requestPatch($uri, $body, $queryParameters, $headers);
    }

    public function requestDelete(string $uri): Response
    {
        return $this->client->requestDelete($uri);
    }
}
