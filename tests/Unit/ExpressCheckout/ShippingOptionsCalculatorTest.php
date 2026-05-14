<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\ExpressCheckout;

use Doctrine\Common\Collections\ArrayCollection;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutShippingRate;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\CartUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\InvalidPayloadException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayload;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReaderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping\ShippingRateAssemblerInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ShippingOptionsCalculator;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\HttpFoundation\Request;

final class ShippingOptionsCalculatorTest extends TestCase
{
    /** @var CartContextInterface&MockObject */
    private CartContextInterface $cartContext;

    /** @var OrderProcessorInterface&MockObject */
    private OrderProcessorInterface $orderProcessor;

    /** @var ShippingMethodsResolverInterface&MockObject */
    private ShippingMethodsResolverInterface $shippingMethodsResolver;

    /** @var ShippingMethodRepositoryInterface<ShippingMethodInterface>&MockObject */
    private ShippingMethodRepositoryInterface $shippingMethodRepository;

    /** @var ExpressCheckoutAddressNormalizerInterface&MockObject */
    private ExpressCheckoutAddressNormalizerInterface $addressNormalizer;

    /** @var ExpressCheckoutPayloadReaderInterface&MockObject */
    private ExpressCheckoutPayloadReaderInterface $payloadReader;

    /** @var ShippingRateAssemblerInterface&MockObject */
    private ShippingRateAssemblerInterface $shippingRateAssembler;

    private ShippingOptionsCalculator $calculator;

    protected function setUp(): void
    {
        $this->cartContext = $this->createMock(CartContextInterface::class);
        $this->orderProcessor = $this->createMock(OrderProcessorInterface::class);
        $this->shippingMethodsResolver = $this->createMock(ShippingMethodsResolverInterface::class);
        $this->shippingMethodRepository = $this->createMock(ShippingMethodRepositoryInterface::class);
        $this->addressNormalizer = $this->createMock(ExpressCheckoutAddressNormalizerInterface::class);
        $this->payloadReader = $this->createMock(ExpressCheckoutPayloadReaderInterface::class);
        $this->shippingRateAssembler = $this->createMock(ShippingRateAssemblerInterface::class);

        $this->calculator = new ShippingOptionsCalculator(
            $this->cartContext,
            $this->orderProcessor,
            $this->shippingMethodsResolver,
            $this->shippingMethodRepository,
            $this->addressNormalizer,
            $this->payloadReader,
            $this->shippingRateAssembler,
        );
    }

    public function test_it_throws_when_cart_is_missing(): void
    {
        $this->cartContext->method('getCart')->willThrowException(new CartNotFoundException());

        $this->expectException(CartUnavailableException::class);

        $this->calculator->calculate(new Request());
    }

    public function test_it_throws_when_cart_is_empty(): void
    {
        $cart = $this->createMock(OrderInterface::class);
        $items = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $items->method('isEmpty')->willReturn(true);
        $cart->method('getItems')->willReturn($items);

        $this->cartContext->method('getCart')->willReturn($cart);

        $this->expectException(CartUnavailableException::class);

        $this->calculator->calculate(new Request());
    }

    public function test_it_throws_when_address_is_missing_from_the_payload(): void
    {
        $this->cartContext->method('getCart')->willReturn($this->createReadyCart());
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload([]));

        $this->expectException(InvalidPayloadException::class);

        $this->calculator->calculate(new Request());
    }

    public function test_it_returns_empty_rates_when_cart_has_no_shipment(): void
    {
        $cart = $this->createReadyCart();
        $shippingAddress = $this->createMock(AddressInterface::class);

        $cart->expects(self::once())->method('setShippingAddress')->with($shippingAddress);
        $cart->method('getBillingAddress')->willReturn(null);
        $cart->expects(self::once())->method('setBillingAddress');

        $cart->method('getShipments')->willReturn(new ArrayCollection([]));
        $cart->method('getItemsTotal')->willReturn(2000);
        $cart->method('getTaxTotal')->willReturn(100);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload(['address' => ['country' => 'US']]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($shippingAddress);

        $this->orderProcessor->expects(self::once())->method('process')->with($cart);
        $this->shippingRateAssembler->expects(self::never())->method('assemble');

        $options = $this->calculator->calculate(new Request());

        self::assertSame([], $options->shippingRates);
        self::assertCount(2, $options->lineItems);
        self::assertSame('Subtotal', $options->lineItems[0]->name);
        self::assertSame(2000, $options->lineItems[0]->amount);
        self::assertSame('Tax', $options->lineItems[1]->name);
        self::assertSame(100, $options->lineItems[1]->amount);
    }

    public function test_it_does_not_reprocess_the_order_when_no_shipping_rate_is_chosen(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $supportedMethod = $this->createShippingMethod('ups');
        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getItemsTotal')->willReturn(5000);
        $cart->method('getTaxTotal')->willReturn(200);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload(['address' => ['country' => 'US']]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));

        $this->shippingMethodsResolver->method('getSupportedMethods')->with($shipment)->willReturn([$supportedMethod]);
        $shipment->method('getMethod')->willReturn(null);

        $rate = new ExpressCheckoutShippingRate('ups', 'UPS', 599, 'usd');
        $this->shippingRateAssembler->method('assemble')->willReturn([$rate]);

        // Address-preview path: only ONE OrderProcessor pass — the second one is the optimization we're guarding.
        $this->orderProcessor->expects(self::once())->method('process')->with($cart);

        // The fallback supported method is set on the shipment after rates are assembled.
        $shipment->expects(self::once())->method('setMethod')->with($supportedMethod);

        $options = $this->calculator->calculate(new Request());

        self::assertSame([$rate], $options->shippingRates);
    }

    public function test_it_reprocesses_the_order_when_a_shipping_rate_is_chosen(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $chosen = $this->createShippingMethod('chosen');
        $other = $this->createShippingMethod('other');

        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getItemsTotal')->willReturn(5000);
        $cart->method('getTaxTotal')->willReturn(200);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload([
            'address' => ['country' => 'US'],
            'shippingRateId' => 'chosen',
        ]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodsResolver->method('getSupportedMethods')->willReturn([$other, $chosen]);
        $shipment->method('getMethod')->willReturn(null);
        $this->shippingRateAssembler->method('assemble')->willReturn([]);

        // Confirm path: TWO OrderProcessor passes — initial + after the chosen rate is applied.
        $this->orderProcessor->expects(self::exactly(2))->method('process')->with($cart);

        $shipment->expects(self::once())->method('setMethod')->with($chosen);

        $this->calculator->calculate(new Request());
    }

    public function test_it_falls_back_to_the_repository_when_chosen_method_is_not_in_supported_set(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $supported = $this->createShippingMethod('ups');
        $external = $this->createShippingMethod('external');

        $cart = $this->createReadyCart();
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getItemsTotal')->willReturn(0);
        $cart->method('getTaxTotal')->willReturn(0);

        $this->cartContext->method('getCart')->willReturn($cart);
        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload([
            'address' => ['country' => 'US'],
            'shippingRateId' => 'external',
        ]));
        $this->addressNormalizer->method('normalizeAddress')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodsResolver->method('getSupportedMethods')->willReturn([$supported]);
        $this->shippingMethodRepository->method('findOneBy')->with(['code' => 'external'])->willReturn($external);
        $this->shippingRateAssembler->method('assemble')->willReturn([]);
        $shipment->method('getMethod')->willReturn(null);

        $shipment->expects(self::once())->method('setMethod')->with($external);
        $this->orderProcessor->expects(self::exactly(2))->method('process');

        $this->calculator->calculate(new Request());
    }

    private function createReadyCart(): OrderInterface&MockObject
    {
        $cart = $this->createMock(OrderInterface::class);
        $items = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $items->method('isEmpty')->willReturn(false);
        $cart->method('getItems')->willReturn($items);

        return $cart;
    }

    private function createShippingMethod(string $code): ShippingMethodInterface&MockObject
    {
        $method = $this->createMock(ShippingMethodInterface::class);
        $method->method('getCode')->willReturn($code);

        return $method;
    }
}
