<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout;

use Doctrine\ORM\EntityManagerInterface;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Shipping\Calculator\DelegatingCalculatorInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ShippingRatesAction
{
    /** @param ShippingMethodRepositoryInterface<ShippingMethodInterface> $shippingMethodRepository */
    public function __construct(
        private CartContextInterface $cartContext,
        private OrderProcessorInterface $orderProcessor,
        private ShippingMethodsResolverInterface $shippingMethodsResolver,
        private DelegatingCalculatorInterface $shippingCalculator,
        private ShippingMethodRepositoryInterface $shippingMethodRepository,
        private ExpressCheckoutAddressNormalizerInterface $addressNormalizer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            return $this->error('Cart not found.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$cart instanceof OrderInterface || $cart->getItems()->isEmpty()) {
            return $this->error('Cart is empty.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = $this->decodePayload($request);
        $addressFields = $payload['address'] ?? null;
        if (!is_array($addressFields)) {
            return $this->error('Missing "address" in request body.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $shippingAddress = $this->addressNormalizer->normalizeAddress($addressFields);
        $cart->setShippingAddress($shippingAddress);
        if (null === $cart->getBillingAddress()) {
            $cart->setBillingAddress(clone $shippingAddress);
        }

        $this->orderProcessor->process($cart);

        $shipment = $cart->getShipments()->first();
        if (!$shipment instanceof ShipmentInterface) {
            $this->entityManager->flush();

            return new JsonResponse([
                'shippingRates' => [],
                'lineItems' => $this->buildLineItems($cart),
            ]);
        }

        $supportedMethods = $this->shippingMethodsResolver->getSupportedMethods($shipment);
        $originalMethod = $shipment->getMethod();

        $rates = $this->buildShippingRates($shipment, $supportedMethods, $cart->getCurrencyCode());

        $shippingRateId = $payload['shippingRateId'] ?? null;
        $chosenMethod = is_string($shippingRateId) ? $this->resolveChosenMethod($shippingRateId, $supportedMethods) : null;

        $shipment->setMethod($chosenMethod ?? $originalMethod ?? $supportedMethods[0] ?? null);

        // Re-process only when the customer actually picked a shipping rate — the address-
        // preview path (shippingaddresschange without shippingRateId) doesn't need a second
        // OrderProcessor pass and the extra round-trip pushed us over Stripe's ECE timeout.
        if (null !== $chosenMethod) {
            $this->orderProcessor->process($cart);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'shippingRates' => $rates,
            'lineItems' => $this->buildLineItems($cart),
        ]);
    }

    /**
     * @param iterable<ShippingMethodInterface> $methods
     *
     * @return list<array{id: string, displayName: string, amount: int, currency: string|null}>
     */
    private function buildShippingRates(ShipmentInterface $shipment, iterable $methods, ?string $currencyCode): array
    {
        $rates = [];
        foreach ($methods as $method) {
            $shipment->setMethod($method);
            $rates[] = [
                'id' => (string) $method->getCode(),
                'displayName' => $method->getName() ?? (string) $method->getCode(),
                'amount' => $this->shippingCalculator->calculate($shipment),
                'currency' => null !== $currencyCode ? strtolower($currencyCode) : null,
            ];
        }

        return $rates;
    }

    /** @param iterable<ShippingMethodInterface> $supportedMethods */
    private function resolveChosenMethod(string $shippingRateId, iterable $supportedMethods): ?ShippingMethodInterface
    {
        foreach ($supportedMethods as $method) {
            if ($method->getCode() === $shippingRateId) {
                return $method;
            }
        }

        /** @var ShippingMethodInterface|null $method */
        $method = $this->shippingMethodRepository->findOneBy(['code' => $shippingRateId]);

        return $method;
    }

    /**
     * Line items rendered by Stripe's Express Checkout Element next to the wallet
     * "Pay" button. Shipping must NOT be included — Stripe adds the cost of the
     * customer-selected `shippingRate` on top of `sum(lineItems)`, so including it
     * here would count shipping twice and trip the guard
     * "amount is less than the total amount of the line items provided".
     *
     * @return list<array{name: string, amount: int}>
     */
    private function buildLineItems(OrderInterface $cart): array
    {
        return [
            ['name' => 'Subtotal', 'amount' => $cart->getItemsTotal()],
            ['name' => 'Tax', 'amount' => $cart->getTaxTotal()],
        ];
    }

    /** @return array<string, mixed> */
    private function decodePayload(Request $request): array
    {
        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
