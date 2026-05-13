<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ExpressCheckoutException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\OrderCompleterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ConfirmAction
{
    public function __construct(
        private OrderCompleterInterface $orderCompleter,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $confirmation = $this->orderCompleter->complete($request);
        } catch (ExpressCheckoutException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($confirmation->toArray());
    }
}
