<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\ProductData;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\OrderItemLineItemProviderInterface;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements OrderItemLineItemProviderInterface<StripeObject>
 */
final class ProductNameProvider implements OrderItemLineItemProviderInterface
{
    public function provideFromOrderItem(
        OrderItemInterface $orderItem,
        PaymentRequestInterface $paymentRequest,
        array &$params,
    ): void {
        $params['name'] = $this->getItemName($orderItem);
    }

    private function getItemName(OrderItemInterface $orderItem): string
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

        if ($productName === $variantName) {
            return $productName;
        }

        return sprintf('%s %s', $productName, $variantName);
    }
}
