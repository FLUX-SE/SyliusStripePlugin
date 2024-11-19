<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\OrderItemLineItemProviderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class OrderItemProductDataNameProvider implements OrderItemLineItemProviderInterface
{
    public function getDetails(
        PaymentRequestInterface $paymentRequest,
        OrderItemInterface $orderItem,
        array &$details
    ): void {
        if (false === isset($details['price_data'])) {
            $details['price_data'] = [];
        }

        if (false === isset($details['price_data']['product_data'])) {
            $details['price_data']['product_data'] = [];
        }

        $details['price_data']['product_data']['name'] = $this->getItemName($orderItem);
    }

    private function getItemName(OrderItemInterface $orderItem): ?string
    {
        $itemName = $this->buildItemName($orderItem);

        return sprintf('%sx - %s', $orderItem->getQuantity(), $itemName);
    }

    private function buildItemName(OrderItemInterface $orderItem): string
    {
        $variantName = (string) $orderItem->getVariantName();
        $productName = (string) $orderItem->getProductName();

        if ('' === $variantName) {
            return $productName;
        }

        $product = $orderItem->getProduct();

        if (null === $product) {
            return $variantName;
        }

        if (false === $product->hasOptions()) {
            return $variantName;
        }

        return sprintf('%s %s', $productName, $variantName);
    }
}
