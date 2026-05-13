<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\ExpressCheckout;

use Doctrine\Common\Collections\ArrayCollection;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\CartUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ChannelUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\InvalidPayloadException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\PaymentIntentNotCreatedException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\PaymentMethodUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ShippingMethodNotFoundException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\OrderCompleter;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayload;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReaderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payment\CapturePaymentRequestDispatcherInterface;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\CoreBundle\Resolver\CustomerResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class OrderCompleterTest extends TestCase
{
    /** @var CartContextInterface&MockObject */
    private CartContextInterface $cartContext;

    /** @var ChannelContextInterface&MockObject */
    private ChannelContextInterface $channelContext;

    /** @var ExpressCheckoutPaymentMethodResolverInterface&MockObject */
    private ExpressCheckoutPaymentMethodResolverInterface $paymentMethodResolver;

    /** @var ExpressCheckoutAddressNormalizerInterface&MockObject */
    private ExpressCheckoutAddressNormalizerInterface $addressNormalizer;

    /** @var ExpressCheckoutPayloadReaderInterface&MockObject */
    private ExpressCheckoutPayloadReaderInterface $payloadReader;

    /** @var CustomerResolverInterface&MockObject */
    private CustomerResolverInterface $customerResolver;

    /** @var StateMachineInterface&MockObject */
    private StateMachineInterface $stateMachine;

    /** @var FactoryInterface&MockObject */
    private FactoryInterface $paymentFactory;

    /** @var ShippingMethodRepositoryInterface<ShippingMethodInterface>&MockObject */
    private ShippingMethodRepositoryInterface $shippingMethodRepository;

    /** @var CapturePaymentRequestDispatcherInterface&MockObject */
    private CapturePaymentRequestDispatcherInterface $capturePaymentRequestDispatcher;

    /** @var AfterUrlProviderInterface&MockObject */
    private AfterUrlProviderInterface $afterUrlProvider;

    /** @var SessionInterface&MockObject */
    private SessionInterface $session;

    private RequestStack $requestStack;

    private OrderCompleter $orderCompleter;

    protected function setUp(): void
    {
        $this->cartContext = $this->createMock(CartContextInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->paymentMethodResolver = $this->createMock(ExpressCheckoutPaymentMethodResolverInterface::class);
        $this->addressNormalizer = $this->createMock(ExpressCheckoutAddressNormalizerInterface::class);
        $this->payloadReader = $this->createMock(ExpressCheckoutPayloadReaderInterface::class);
        $this->customerResolver = $this->createMock(CustomerResolverInterface::class);
        $this->stateMachine = $this->createMock(StateMachineInterface::class);
        $this->paymentFactory = $this->createMock(FactoryInterface::class);
        $this->shippingMethodRepository = $this->createMock(ShippingMethodRepositoryInterface::class);
        $this->capturePaymentRequestDispatcher = $this->createMock(CapturePaymentRequestDispatcherInterface::class);
        $this->afterUrlProvider = $this->createMock(AfterUrlProviderInterface::class);

        $this->session = $this->createMock(SessionInterface::class);
        $this->requestStack = new RequestStack();
        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        $this->orderCompleter = new OrderCompleter(
            $this->cartContext,
            $this->channelContext,
            $this->paymentMethodResolver,
            $this->addressNormalizer,
            $this->payloadReader,
            $this->customerResolver,
            $this->stateMachine,
            $this->paymentFactory,
            $this->shippingMethodRepository,
            $this->capturePaymentRequestDispatcher,
            $this->afterUrlProvider,
            $this->requestStack,
        );
    }

    public function test_it_completes_the_order_and_returns_the_client_secret_and_return_url(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $shippingAddress = $this->createMock(AddressInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $shipment = $this->createMock(ShipmentInterface::class);
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $payment = $this->createMock(PaymentInterface::class);
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);

        $cart = $this->createReadyCart();
        $cart->method('getChannel')->willReturn($channel);
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getTotal')->willReturn(4999);
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getId')->willReturn(42);
        // Guest flow — cart has no customer yet, so OrderCompleter resolves one by email.
        $cart->method('getCustomer')->willReturn(null);

        $this->session->expects(self::once())->method('set')->with('sylius_order_id', 42);

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->cartContext->method('getCart')->willReturn($cart);
        $this->paymentMethodResolver->method('resolveForChannel')->with($channel)->willReturn($paymentMethod);

        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload([
            'billingDetails' => ['email' => 'shopper@example.com'],
            'shippingAddress' => ['name' => 'Jane Doe'],
            'shippingRate' => ['id' => 'ups_ground'],
        ]));

        $this->addressNormalizer->method('normalizeShipping')->willReturn($shippingAddress);

        $this->customerResolver->method('resolve')->with('shopper@example.com')->willReturn($customer);
        $this->shippingMethodRepository->method('findOneBy')->with(['code' => 'ups_ground'])->willReturn($shippingMethod);

        $cart->expects(self::once())->method('setCustomer')->with($customer);
        $cart->expects(self::once())->method('setShippingAddress')->with($shippingAddress);
        // Billing address is always a clone of the shipping address — wallet popups
        // never expose a separate billing picker, so cloning is the correct intent.
        $cart->expects(self::once())->method('setBillingAddress')->with(self::isInstanceOf(AddressInterface::class));
        $cart->expects(self::once())->method('addPayment')->with($payment);

        $shipment->expects(self::once())->method('setMethod')->with($shippingMethod);

        $this->paymentFactory->method('createNew')->willReturn($payment);
        $payment->expects(self::once())->method('setMethod')->with($paymentMethod);
        $payment->expects(self::once())->method('setCurrencyCode')->with('USD');
        $payment->expects(self::once())->method('setAmount')->with(4999);

        $transitionCalls = [];
        $this->stateMachine->expects(self::exactly(4))
            ->method('apply')
            ->willReturnCallback(function ($subject, string $graph, string $transition) use (&$transitionCalls, $cart): void {
                self::assertSame($cart, $subject);
                self::assertSame('sylius_order_checkout', $graph);
                $transitionCalls[] = $transition;
            });

        $this->capturePaymentRequestDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($payment, $paymentMethod)
            ->willReturn($paymentRequest);

        $paymentRequest->method('getResponseData')->willReturn(['client_secret' => 'pi_123_secret_abc']);
        $this->afterUrlProvider->method('getUrl')
            ->with($paymentRequest, AfterUrlProviderInterface::ACTION_URL)
            ->willReturn('https://example.com/return');

        $confirmation = $this->orderCompleter->complete(new Request());

        self::assertSame(['address', 'select_shipping', 'select_payment', 'complete'], $transitionCalls);
        self::assertSame('pi_123_secret_abc', $confirmation->clientSecret);
        self::assertSame('https://example.com/return', $confirmation->returnUrl);
    }

    public function test_it_preserves_existing_customer_when_cart_already_has_one(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $shippingAddress = $this->createMock(AddressInterface::class);
        $existingCustomer = $this->createMock(CustomerInterface::class);
        $shipment = $this->createMock(ShipmentInterface::class);
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $payment = $this->createMock(PaymentInterface::class);
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);

        $cart = $this->createReadyCart();
        $cart->method('getChannel')->willReturn($channel);
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getTotal')->willReturn(4999);
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getId')->willReturn(42);
        // Logged-in customer already linked to the cart by Sylius's cart context.
        $cart->method('getCustomer')->willReturn($existingCustomer);

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->cartContext->method('getCart')->willReturn($cart);
        $this->paymentMethodResolver->method('resolveForChannel')->with($channel)->willReturn($paymentMethod);

        $this->payloadReader->method('read')->willReturn(new ExpressCheckoutPayload([
            'billingDetails' => ['email' => 'gpay-account@example.com'],
            'shippingAddress' => ['name' => 'Jane Doe'],
            'shippingRate' => ['id' => 'ups_ground'],
        ]));

        $this->addressNormalizer->method('normalizeShipping')->willReturn($shippingAddress);
        $this->shippingMethodRepository->method('findOneBy')->with(['code' => 'ups_ground'])->willReturn($shippingMethod);

        $cart->expects(self::never())->method('setCustomer');
        $this->customerResolver->expects(self::never())->method('resolve');

        $this->paymentFactory->method('createNew')->willReturn($payment);
        $this->capturePaymentRequestDispatcher->method('dispatch')->willReturn($paymentRequest);
        $paymentRequest->method('getResponseData')->willReturn(['client_secret' => 'pi_123_secret_abc']);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_channel_is_missing(): void
    {
        $this->channelContext->method('getChannel')->willThrowException(new \Sylius\Component\Channel\Context\ChannelNotFoundException());

        $this->expectException(ChannelUnavailableException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_cart_is_missing(): void
    {
        $this->channelContext->method('getChannel')->willReturn($this->createMock(ChannelInterface::class));
        $this->cartContext->method('getCart')->willThrowException(new \Sylius\Component\Order\Context\CartNotFoundException());

        $this->expectException(CartUnavailableException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_payment_method_is_missing(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $cart = $this->createReadyCart();
        $cart->method('getChannel')->willReturn($channel);

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->cartContext->method('getCart')->willReturn($cart);
        $this->paymentMethodResolver->method('resolveForChannel')->willReturn(null);

        $this->expectException(PaymentMethodUnavailableException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_email_is_missing_from_the_payload(): void
    {
        $this->setUpUpToPayload(new ExpressCheckoutPayload([]));

        $this->expectException(InvalidPayloadException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_shipping_rate_id_is_missing(): void
    {
        $this->setUpUpToPayload(new ExpressCheckoutPayload(['billingDetails' => ['email' => 'a@b.c']]));
        $this->addressNormalizer->method('normalizeShipping')->willReturn($this->createMock(AddressInterface::class));

        $this->expectException(InvalidPayloadException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_shipping_method_is_not_found(): void
    {
        $this->setUpUpToPayload(new ExpressCheckoutPayload([
            'billingDetails' => ['email' => 'a@b.c'],
            'shippingRate' => ['id' => 'unknown'],
        ]));
        $this->addressNormalizer->method('normalizeShipping')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodRepository->method('findOneBy')->with(['code' => 'unknown'])->willReturn(null);

        $this->expectException(ShippingMethodNotFoundException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_cart_has_no_shipment(): void
    {
        $cart = $this->setUpUpToPayload(new ExpressCheckoutPayload([
            'billingDetails' => ['email' => 'a@b.c'],
            'shippingRate' => ['id' => 'ups'],
        ]));
        $cart->method('getShipments')->willReturn(new ArrayCollection([]));

        $this->addressNormalizer->method('normalizeShipping')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodRepository->method('findOneBy')->willReturn($this->createMock(ShippingMethodInterface::class));
        $this->customerResolver->method('resolve')->willReturn($this->createMock(CustomerInterface::class));

        $this->expectException(ShippingMethodNotFoundException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_cart_has_no_currency_code(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $cart = $this->setUpUpToPayload(new ExpressCheckoutPayload([
            'billingDetails' => ['email' => 'a@b.c'],
            'shippingRate' => ['id' => 'ups'],
        ]));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getCurrencyCode')->willReturn(null);

        $this->addressNormalizer->method('normalizeShipping')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodRepository->method('findOneBy')->willReturn($this->createMock(ShippingMethodInterface::class));
        $this->customerResolver->method('resolve')->willReturn($this->createMock(CustomerInterface::class));
        $this->paymentFactory->method('createNew')->willReturn($this->createMock(PaymentInterface::class));

        $this->expectException(PaymentIntentNotCreatedException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_throws_when_stripe_returns_no_client_secret(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $cart = $this->setUpUpToPayload(new ExpressCheckoutPayload([
            'billingDetails' => ['email' => 'a@b.c'],
            'shippingRate' => ['id' => 'ups'],
        ]));
        $cart->method('getShipments')->willReturn(new ArrayCollection([$shipment]));
        $cart->method('getCurrencyCode')->willReturn('USD');
        $cart->method('getTotal')->willReturn(100);

        $this->addressNormalizer->method('normalizeShipping')->willReturn($this->createMock(AddressInterface::class));
        $this->shippingMethodRepository->method('findOneBy')->willReturn($this->createMock(ShippingMethodInterface::class));
        $this->customerResolver->method('resolve')->willReturn($this->createMock(CustomerInterface::class));
        $this->paymentFactory->method('createNew')->willReturn($this->createMock(PaymentInterface::class));

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getResponseData')->willReturn([]);

        $this->capturePaymentRequestDispatcher->method('dispatch')->willReturn($paymentRequest);

        $this->expectException(PaymentIntentNotCreatedException::class);

        $this->orderCompleter->complete(new Request());
    }

    public function test_it_rewraps_normalizer_exceptions_as_invalid_payload(): void
    {
        $this->setUpUpToPayload(new ExpressCheckoutPayload([
            'billingDetails' => ['email' => 'a@b.c'],
        ]));
        $this->addressNormalizer->method('normalizeShipping')->willThrowException(new \LogicException('bad address'));

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('bad address');

        $this->orderCompleter->complete(new Request());
    }

    private function setUpUpToPayload(ExpressCheckoutPayload $payload): OrderInterface&MockObject
    {
        $channel = $this->createMock(ChannelInterface::class);
        $cart = $this->createReadyCart();
        $cart->method('getChannel')->willReturn($channel);

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->cartContext->method('getCart')->willReturn($cart);
        $this->paymentMethodResolver->method('resolveForChannel')->willReturn($this->createMock(PaymentMethodInterface::class));
        $this->payloadReader->method('read')->willReturn($payload);

        return $cart;
    }

    private function createReadyCart(): OrderInterface&MockObject
    {
        $cart = $this->createMock(OrderInterface::class);
        $items = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $items->method('isEmpty')->willReturn(false);
        $cart->method('getItems')->willReturn($items);

        return $cart;
    }
}
