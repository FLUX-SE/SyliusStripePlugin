<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\OrderItemLineItemProviderInterface;
use Stripe\LineItem;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements OrderItemLineItemProviderInterface<LineItem>
 */
final readonly class OrderItemPriceDataProvider implements OrderItemLineItemProviderInterface
{
    /**
     * @param OrderItemLineItemProviderInterface<StripeObject>[] $orderItemPriceDataProviders
     */
    public function __construct(
        private iterable $orderItemPriceDataProviders,
    ) {
    }

    public function provideFromOrderItem(
        OrderItemInterface $orderItem,
        PaymentRequestInterface $paymentRequest,
        array &$params,
    ): void {
        /** @var array<key-of<StripeObject>, mixed> $priceData */
        $priceData = [];

        foreach ($this->orderItemPriceDataProviders as $orderItemPriceDataProvider) {
            $orderItemPriceDataProvider->provideFromOrderItem($orderItem, $paymentRequest, $priceData);
        }

        if ([] === $priceData) {
            return;
        }

        if (false === isset($params['price_data'])) {
            $params['price_data'] = [];
        }

        $params['price_data'] += $priceData;
    }
}
