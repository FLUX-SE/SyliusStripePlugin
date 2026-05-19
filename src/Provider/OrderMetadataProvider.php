<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 * @implements InnerParamsProviderInterface<T>
 */
final readonly class OrderMetadataProvider implements InnerParamsProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();
        if (null === $order) {
            return;
        }

        $number = $order->getNumber();
        if (null !== $number) {
            $params['order_number'] = $number;
        }

        // Stripe metadata values must be strings; total is expressed in minor units (e.g. cents).
        $params['order_total'] = (string) $order->getTotal();

        $currency = $order->getCurrencyCode();
        if (null !== $currency) {
            $params['currency'] = $currency;
        }

        $locale = $order->getLocaleCode();
        if (null !== $locale) {
            $params['locale'] = $locale;
        }
    }
}
