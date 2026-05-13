<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\ConfigurationProviderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ExpressCheckoutException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class ConfigurationAction
{
    public function __construct(
        private ConfigurationProviderInterface $configurationProvider,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            $configuration = $this->configurationProvider->provide();
        } catch (ExpressCheckoutException) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($configuration->toArray());
    }
}
