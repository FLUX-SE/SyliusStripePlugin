<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Resolver;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;

final readonly class ExpressCheckoutPaymentMethodResolver implements ExpressCheckoutPaymentMethodResolverInterface
{
    public const PREFERRED_FACTORY_NAME = 'stripe_web_elements';

    /**
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     * @param list<string> $supportedFactoryNames
     */
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private array $supportedFactoryNames,
    ) {
    }

    public function resolveForChannel(ChannelInterface $channel): ?PaymentMethodInterface
    {
        $candidates = $this->findCandidates($channel);

        if ([] === $candidates) {
            return null;
        }

        foreach ($candidates as $paymentMethod) {
            if (self::PREFERRED_FACTORY_NAME === $paymentMethod->getGatewayConfig()?->getFactoryName()) {
                return $paymentMethod;
            }
        }

        return $candidates[0];
    }

    public function getPublishableKey(PaymentMethodInterface $paymentMethod): ?string
    {
        $config = $paymentMethod->getGatewayConfig()?->getConfig() ?? [];
        $publishableKey = $config['publishable_key'] ?? null;

        return is_string($publishableKey) && '' !== $publishableKey ? $publishableKey : null;
    }

    /** @return list<PaymentMethodInterface> */
    private function findCandidates(ChannelInterface $channel): array
    {
        $candidates = [];
        foreach ($this->paymentMethodRepository->findEnabledForChannel($channel) as $paymentMethod) {
            $gatewayConfig = $paymentMethod->getGatewayConfig();
            if (null === $gatewayConfig) {
                continue;
            }

            if (!in_array($gatewayConfig->getFactoryName(), $this->supportedFactoryNames, true)) {
                continue;
            }

            $config = $gatewayConfig->getConfig();
            if (true !== ($config['enable_express_checkout'] ?? false)) {
                continue;
            }

            $candidates[] = $paymentMethod;
        }

        return $candidates;
    }
}
