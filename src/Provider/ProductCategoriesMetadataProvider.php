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
final readonly class ProductCategoriesMetadataProvider implements InnerParamsProviderInterface
{
    private const CATEGORIES_CAP = 10;

    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();
        if (null === $order) {
            return;
        }

        $codes = [];
        foreach ($order->getItems() as $item) {
            $code = $item->getProduct()?->getMainTaxon()?->getCode();
            if (null === $code) {
                continue;
            }
            $codes[strtolower($code)] = true;
            if (count($codes) >= self::CATEGORIES_CAP) {
                break;
            }
        }

        if ([] === $codes) {
            return;
        }

        $params['product_categories'] = implode(',', array_keys($codes));
    }
}
