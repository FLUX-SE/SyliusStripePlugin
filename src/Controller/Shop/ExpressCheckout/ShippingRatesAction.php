<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ExpressCheckoutException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ShippingOptionsCalculatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ShippingRatesAction
{
    public function __construct(
        private ShippingOptionsCalculatorInterface $shippingOptionsCalculator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $options = $this->shippingOptionsCalculator->calculate($request);
        } catch (ExpressCheckoutException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($options->toArray());
    }
}
