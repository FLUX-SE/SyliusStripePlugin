<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ExpressCheckoutException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ExpressCheckoutCsrf;
use FluxSE\SyliusStripePlugin\ExpressCheckout\OrderCompleterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class ConfirmAction
{
    public function __construct(
        private OrderCompleterInterface $orderCompleter,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $token = $request->headers->get(ExpressCheckoutCsrf::HEADER_NAME, '');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(ExpressCheckoutCsrf::TOKEN_ID, $token))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $confirmation = $this->orderCompleter->complete($request);
        } catch (ExpressCheckoutException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($confirmation->toArray());
    }
}
