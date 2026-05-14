<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutLineItem;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutShippingOptions;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\CartUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\InvalidPayloadException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReaderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping\ShippingRateAssemblerInterface;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ShippingOptionsCalculator implements ShippingOptionsCalculatorInterface
{
    /** @param ShippingMethodRepositoryInterface<ShippingMethodInterface> $shippingMethodRepository */
    public function __construct(
        private CartContextInterface $cartContext,
        private OrderProcessorInterface $orderProcessor,
        private ShippingMethodsResolverInterface $shippingMethodsResolver,
        private ShippingMethodRepositoryInterface $shippingMethodRepository,
        private ExpressCheckoutAddressNormalizerInterface $addressNormalizer,
        private ExpressCheckoutPayloadReaderInterface $payloadReader,
        private ShippingRateAssemblerInterface $shippingRateAssembler,
    ) {
    }

    public function calculate(Request $request): ExpressCheckoutShippingOptions
    {
        $cart = $this->resolveCart();

        $payload = $this->payloadReader->read($request);
        $addressFields = $payload->getAddress();
        if (null === $addressFields) {
            throw InvalidPayloadException::missingAddress();
        }

        $shippingAddress = $this->addressNormalizer->normalizeAddress($addressFields);
        $cart->setShippingAddress($shippingAddress);
        if (null === $cart->getBillingAddress()) {
            $cart->setBillingAddress(clone $shippingAddress);
        }

        $this->orderProcessor->process($cart);

        $shipment = $cart->getShipments()->first();
        if (!$shipment instanceof ShipmentInterface) {
            return new ExpressCheckoutShippingOptions(
                shippingRates: [],
                lineItems: $this->buildLineItems($cart),
            );
        }

        $supportedMethods = $this->shippingMethodsResolver->getSupportedMethods($shipment);
        $originalMethod = $shipment->getMethod();

        $rates = $this->shippingRateAssembler->assemble($shipment, $supportedMethods, $cart->getCurrencyCode());

        $shippingRateId = $payload->getShippingRateId();
        $chosenMethod = null !== $shippingRateId ? $this->resolveChosenMethod($shippingRateId, $supportedMethods) : null;

        $shipment->setMethod($chosenMethod ?? $originalMethod ?? $supportedMethods[0] ?? null);

        // Re-process only when the customer actually picked a shipping rate — the address-
        // preview path (shippingaddresschange without shippingRateId) doesn't need a second
        // OrderProcessor pass and the extra round-trip pushed us over Stripe's ECE timeout.
        if (null !== $chosenMethod) {
            $this->orderProcessor->process($cart);
        }

        return new ExpressCheckoutShippingOptions(
            shippingRates: $rates,
            lineItems: $this->buildLineItems($cart),
        );
    }

    private function resolveCart(): OrderInterface
    {
        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            throw CartUnavailableException::notFound();
        }

        if (!$cart instanceof OrderInterface) {
            throw CartUnavailableException::notFound();
        }

        if ($cart->getItems()->isEmpty()) {
            throw CartUnavailableException::empty();
        }

        return $cart;
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
     * @return list<ExpressCheckoutLineItem>
     */
    private function buildLineItems(OrderInterface $cart): array
    {
        return [
            new ExpressCheckoutLineItem(name: 'Subtotal', amount: $cart->getItemsTotal()),
            new ExpressCheckoutLineItem(name: 'Tax', amount: $cart->getTaxTotal()),
        ];
    }
}
