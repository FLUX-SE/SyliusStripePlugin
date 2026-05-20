<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Factory;

use FluxSE\SyliusStripePlugin\Stripe\Configurator\StripeConfiguratorInterface;
use Stripe\StripeClient;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Webmozart\Assert\Assert;

final readonly class ClientFactory implements ClientFactoryInterface
{
    /** @param class-string<StripeClient> $className */
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
            $paymentMethod->getCode(),
        ));

        /** @var string|null $secretKey */
        $secretKey = $gatewayConfig->getConfig()['secret_key'] ?? null;

        if (is_string($secretKey) && str_starts_with($secretKey, 'sk_')) {
            trigger_deprecation(
                'flux-se/sylius-stripe-plugin',
                '2.0',
                'Using a Stripe secret key (sk_…) for the "%s" payment method is no longer supported since Stripe plugin 2.0. The form validator rejects sk_… values; this payment method keeps operating only because its key was saved before the upgrade. Replace it with a Restricted API Key (rk_…) from the Sylius Stripe App (https://marketplace.stripe.com/apps/install/link/com.sylius.stripe).',
                $paymentMethod->getCode(),
            );
        }

        return $this->createNew([
            'api_key' => $secretKey,
        ]);
    }
}
