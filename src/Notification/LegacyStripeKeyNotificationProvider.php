<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Notification;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use Sylius\Bundle\AdminBundle\Notification\NotificationProviderInterface;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Repository\PaymentMethodRepositoryInterface;

final readonly class LegacyStripeKeyNotificationProvider implements NotificationProviderInterface
{
    /**
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     * @param list<string> $stripeFactoryNames
     */
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        private LegacyKeyDetectorInterface $legacyKeyDetector,
        private array $stripeFactoryNames,
    ) {
    }

    public function getNotifications(array $context = []): array
    {
        $notifications = [];

        /** @var list<PaymentMethodInterface> $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->findBy(['enabled' => true]);

        foreach ($paymentMethods as $paymentMethod) {
            $gatewayConfig = $paymentMethod->getGatewayConfig();
            if ($gatewayConfig === null) {
                continue;
            }

            if (!in_array($this->gatewayFactoryNameProvider->provide($paymentMethod), $this->stripeFactoryNames, true)) {
                continue;
            }

            /** @var mixed $secretKey */
            $secretKey = $gatewayConfig->getConfig()['secret_key'] ?? null;
            if (!is_string($secretKey) && $secretKey !== null) {
                continue;
            }

            if (!$this->legacyKeyDetector->isLegacy($secretKey)) {
                continue;
            }

            $notifications[sprintf('legacy_stripe_secret_key.%s', (string) $paymentMethod->getId())] = [
                'message' => 'flux_se_sylius_stripe_plugin.admin.notification.legacy_secret_key',
                '%payment_method_name%' => (string) $paymentMethod->getName(),
                'route' => 'sylius_admin_payment_method_update',
                'route_parameters' => ['id' => $paymentMethod->getId()],
            ];
        }

        return $notifications;
    }

    public function supports(array $context = []): bool
    {
        return true;
    }
}
