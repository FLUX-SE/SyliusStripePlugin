<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\ExpressCheckout;

use Doctrine\Common\Collections\ArrayCollection;
use FluxSE\SyliusStripePlugin\ExpressCheckout\ConfigurationProvider;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\CartUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ChannelUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\PaymentMethodUnavailableException;
use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;

final class ConfigurationProviderTest extends TestCase
{
    /** @var CartContextInterface&MockObject */
    private CartContextInterface $cartContext;

    /** @var ChannelContextInterface&MockObject */
    private ChannelContextInterface $channelContext;

    /** @var ExpressCheckoutPaymentMethodResolverInterface&MockObject */
    private ExpressCheckoutPaymentMethodResolverInterface $paymentMethodResolver;

    private ConfigurationProvider $configurationProvider;

    protected function setUp(): void
    {
        $this->cartContext = $this->createMock(CartContextInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->paymentMethodResolver = $this->createMock(ExpressCheckoutPaymentMethodResolverInterface::class);

        $this->configurationProvider = new ConfigurationProvider(
            $this->cartContext,
            $this->channelContext,
            $this->paymentMethodResolver,
        );
    }

    public function test_it_returns_a_configuration_dto_for_a_ready_cart(): void
    {
        $country = $this->createMock(CountryInterface::class);
        $country->method('getCode')->willReturn('US');

        $channel = $this->createChannel('Test Shop', [$country]);
        $cart = $this->createCart('USD', 4299);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getCode')->willReturn('stripe_ece');

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->cartContext->method('getCart')->willReturn($cart);
        $this->paymentMethodResolver->method('resolveForChannel')->with($channel)->willReturn($paymentMethod);
        $this->paymentMethodResolver->method('getPublishableKey')->with($paymentMethod)->willReturn('pk_test_123');

        $configuration = $this->configurationProvider->provide();

        self::assertSame('pk_test_123', $configuration->publishableKey);
        self::assertSame('stripe_ece', $configuration->paymentMethodCode);
        self::assertSame('usd', $configuration->currency);
        self::assertSame(4299, $configuration->amount);
        self::assertSame('US', $configuration->country);
        self::assertSame(['US'], $configuration->allowedCountryCodes);
        self::assertSame('Test Shop', $configuration->merchantName);
        self::assertTrue($configuration->shippingRequired);
    }

    public function test_it_falls_back_to_channel_base_currency_when_cart_has_no_currency(): void
    {
        $currency = $this->createMock(CurrencyInterface::class);
        $currency->method('getCode')->willReturn('EUR');

        $channel = $this->createChannel('Shop', []);
        $channel->method('getBaseCurrency')->willReturn($currency);

        $cart = $this->createCart(null, 1000);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getCode')->willReturn('ece');

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->cartContext->method('getCart')->willReturn($cart);
        $this->paymentMethodResolver->method('resolveForChannel')->willReturn($paymentMethod);
        $this->paymentMethodResolver->method('getPublishableKey')->willReturn('pk_test');

        $configuration = $this->configurationProvider->provide();

        self::assertSame('eur', $configuration->currency);
        self::assertNull($configuration->country);
        self::assertSame([], $configuration->allowedCountryCodes);
        self::assertSame('Shop', $configuration->merchantName);
    }

    public function test_it_defaults_merchant_name_when_channel_name_is_null(): void
    {
        $channel = $this->createChannel(null, []);
        $cart = $this->createCart('USD', 100);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getCode')->willReturn('ece');

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->cartContext->method('getCart')->willReturn($cart);
        $this->paymentMethodResolver->method('resolveForChannel')->willReturn($paymentMethod);
        $this->paymentMethodResolver->method('getPublishableKey')->willReturn('pk_test');

        self::assertSame('Shop', $this->configurationProvider->provide()->merchantName);
    }

    public function test_it_throws_when_channel_is_missing(): void
    {
        $this->channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());

        $this->expectException(ChannelUnavailableException::class);

        $this->configurationProvider->provide();
    }

    public function test_it_throws_when_cart_is_missing(): void
    {
        $this->channelContext->method('getChannel')->willReturn($this->createChannel('Shop', []));
        $this->cartContext->method('getCart')->willThrowException(new CartNotFoundException());

        $this->expectException(CartUnavailableException::class);

        $this->configurationProvider->provide();
    }

    public function test_it_throws_when_cart_is_empty(): void
    {
        $this->channelContext->method('getChannel')->willReturn($this->createChannel('Shop', []));
        $this->cartContext->method('getCart')->willReturn($this->createCart('USD', 0, isEmpty: true));

        $this->expectException(CartUnavailableException::class);

        $this->configurationProvider->provide();
    }

    public function test_it_throws_when_no_payment_method_is_configured(): void
    {
        $this->channelContext->method('getChannel')->willReturn($this->createChannel('Shop', []));
        $this->cartContext->method('getCart')->willReturn($this->createCart('USD', 100));
        $this->paymentMethodResolver->method('resolveForChannel')->willReturn(null);

        $this->expectException(PaymentMethodUnavailableException::class);

        $this->configurationProvider->provide();
    }

    public function test_it_throws_when_publishable_key_is_missing(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->channelContext->method('getChannel')->willReturn($this->createChannel('Shop', []));
        $this->cartContext->method('getCart')->willReturn($this->createCart('USD', 100));
        $this->paymentMethodResolver->method('resolveForChannel')->willReturn($paymentMethod);
        $this->paymentMethodResolver->method('getPublishableKey')->willReturn(null);

        $this->expectException(PaymentMethodUnavailableException::class);

        $this->configurationProvider->provide();
    }

    public function test_it_throws_when_neither_cart_nor_channel_have_a_currency(): void
    {
        $channel = $this->createChannel('Shop', []);
        $channel->method('getBaseCurrency')->willReturn(null);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getCode')->willReturn('ece');

        $this->channelContext->method('getChannel')->willReturn($channel);
        $this->cartContext->method('getCart')->willReturn($this->createCart(null, 100));
        $this->paymentMethodResolver->method('resolveForChannel')->willReturn($paymentMethod);
        $this->paymentMethodResolver->method('getPublishableKey')->willReturn('pk_test');

        $this->expectException(ChannelUnavailableException::class);

        $this->configurationProvider->provide();
    }

    /** @param list<CountryInterface> $countries */
    private function createChannel(?string $name, array $countries): ChannelInterface&MockObject
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getName')->willReturn($name);
        $channel->method('getCountries')->willReturn(new ArrayCollection($countries));

        return $channel;
    }

    private function createCart(?string $currencyCode, int $total, bool $isEmpty = false): OrderInterface&MockObject
    {
        $cart = $this->createMock(OrderInterface::class);
        $cart->method('getCurrencyCode')->willReturn($currencyCode);
        $cart->method('getTotal')->willReturn($total);

        $items = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $items->method('isEmpty')->willReturn($isEmpty);
        $cart->method('getItems')->willReturn($items);

        return $cart;
    }
}
