<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout;

use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class ConfigurationAction
{
    public function __construct(
        private CartContextInterface $cartContext,
        private ChannelContextInterface $channelContext,
        private ExpressCheckoutPaymentMethodResolverInterface $paymentMethodResolver,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return $this->noContent();
        }

        if (!$channel instanceof ChannelInterface) {
            return $this->noContent();
        }

        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            return $this->noContent();
        }

        if (!$cart instanceof OrderInterface || $cart->getItems()->isEmpty()) {
            return $this->noContent();
        }

        $paymentMethod = $this->paymentMethodResolver->resolveForChannel($channel);
        if (null === $paymentMethod) {
            return $this->noContent();
        }

        $publishableKey = $this->paymentMethodResolver->getPublishableKey($paymentMethod);
        if (null === $publishableKey) {
            return $this->noContent();
        }

        $currencyCode = $cart->getCurrencyCode() ?? $channel->getBaseCurrency()?->getCode();
        if (null === $currencyCode) {
            return $this->noContent();
        }

        return new JsonResponse([
            'publishableKey' => $publishableKey,
            'paymentMethodCode' => $paymentMethod->getCode(),
            'currency' => strtolower($currencyCode),
            'amount' => $cart->getTotal(),
            'country' => $this->resolveMerchantCountry($channel),
            'shippingRequired' => true,
            'allowedCountryCodes' => $this->extractCountryCodes($channel),
            'merchantName' => $channel->getName() ?? 'Shop',
        ]);
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

    private function noContent(): Response
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
