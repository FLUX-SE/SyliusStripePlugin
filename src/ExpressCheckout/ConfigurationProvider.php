<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutConfiguration;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\CartUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ChannelUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\PaymentMethodUnavailableException;
use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;

final readonly class ConfigurationProvider implements ConfigurationProviderInterface
{
    public function __construct(
        private CartContextInterface $cartContext,
        private ChannelContextInterface $channelContext,
        private ExpressCheckoutPaymentMethodResolverInterface $paymentMethodResolver,
    ) {
    }

    public function provide(): ExpressCheckoutConfiguration
    {
        $channel = $this->resolveChannel();
        $cart = $this->resolveCart();

        $paymentMethod = $this->paymentMethodResolver->resolveForChannel($channel);
        if (null === $paymentMethod) {
            throw PaymentMethodUnavailableException::notConfigured();
        }

        $publishableKey = $this->paymentMethodResolver->getPublishableKey($paymentMethod);
        if (null === $publishableKey) {
            throw PaymentMethodUnavailableException::missingPublishableKey();
        }

        $currencyCode = $cart->getCurrencyCode() ?? $channel->getBaseCurrency()?->getCode();
        if (null === $currencyCode) {
            throw ChannelUnavailableException::withoutCurrency();
        }

        return new ExpressCheckoutConfiguration(
            publishableKey: $publishableKey,
            paymentMethodCode: (string) $paymentMethod->getCode(),
            currency: strtolower($currencyCode),
            amount: $cart->getTotal(),
            country: $this->resolveMerchantCountry($channel),
            allowedCountryCodes: $this->extractCountryCodes($channel),
            merchantName: $channel->getName() ?? 'Shop',
            shippingRequired: $cart->isShippingRequired(),
        );
    }

    private function resolveChannel(): ChannelInterface
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            throw ChannelUnavailableException::notFound();
        }

        if (!$channel instanceof ChannelInterface) {
            throw ChannelUnavailableException::notFound();
        }

        return $channel;
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

    private function resolveMerchantCountry(ChannelInterface $channel): ?string
    {
        $country = $channel->getCountries()->first();

        return false === $country ? null : $country->getCode();
    }

    /** @return list<string> */
    private function extractCountryCodes(ChannelInterface $channel): array
    {
        $codes = [];
        foreach ($channel->getCountries() as $country) {
            $code = $country->getCode();
            if (null !== $code) {
                $codes[] = $code;
            }
        }

        return $codes;
    }
}
