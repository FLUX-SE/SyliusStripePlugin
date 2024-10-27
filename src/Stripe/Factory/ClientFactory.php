<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Factory;

use FluxSE\SyliusStripePlugin\Stripe\Configurator\StripeConfiguratorInterface;
use Stripe\StripeClientInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Webmozart\Assert\Assert;

final readonly class ClientFactory implements ClientFactoryInterface
{

    /**
     * @param class-string<StripeClientInterface> $className
     */
    public function __construct(
        private string $className,
        private StripeConfiguratorInterface $stripeConfigurator,
    ) {
    }

    public function createNew(array $config): StripeClientInterface
    {
        $this->stripeConfigurator->configure($config);

        return new $this->className($config);
    }

    public function createFromPaymentMethod(PaymentMethodInterface $paymentMethod): StripeClientInterface
    {
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        Assert::notNull($gatewayConfig, sprintf(
            'The payment method (code: %s) has not been configured.',
            $paymentMethod->getCode()
        ));

        /** @var string|null  $secretKey */
        $secretKey = $gatewayConfig->getConfig()['secret_key'] ?? null;

        return $this->createNew([
            'api_key' => $secretKey,
        ]);
    }
}
