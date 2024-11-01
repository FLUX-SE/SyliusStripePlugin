<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Factory;

use FluxSE\SyliusStripePlugin\Stripe\Configurator\StripeConfiguratorInterface;
use Stripe\StripeClient;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Webmozart\Assert\Assert;

final readonly class ClientFactory implements ClientFactoryInterface
{

    /**
     * @param class-string<StripeClient> $className
     */
    public function __construct(
        private string $className,
        private StripeConfiguratorInterface $stripeConfigurator,
    ) {
    }

    public function createNew(array $config): StripeClient
    {
        $this->stripeConfigurator->configure($config);

        return new $this->className($config);
    }

    public function createFromPaymentMethod(PaymentMethodInterface $paymentMethod): StripeClient
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
