<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\OrderItemLineItemProviderInterface;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements OrderItemLineItemProviderInterface<StripeObject>
 */
final class OrderItemProductDataProvider implements OrderItemLineItemProviderInterface
{
    /**
     * @param OrderItemLineItemProviderInterface<StripeObject>[] $orderItemProductDataProviders
     */
    public function __construct(
        private iterable $orderItemProductDataProviders,
    ) {
    }

    public function provideFromOrderItem(
        OrderItemInterface $orderItem,
        PaymentRequestInterface $paymentRequest,
        array &$params
    ): void {

        /** @var array<key-of<StripeObject>, mixed> $productData */
        $productData = [];

        foreach ($this->orderItemProductDataProviders as $orderItemProductDataProvider) {
            $orderItemProductDataProvider->provideFromOrderItem($orderItem, $paymentRequest, $productData);
        }

        if ([] === $productData) {
            return;
        }

        if (false === isset($params['product_data'])) {
            $params['product_data'] = [];
        }

        $params['product_data'] += $productData;
    }
}
