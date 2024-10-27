<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

class LinetItemNameProvider implements LinetItemNameProviderInterface
{
    public function getItemName(PaymentRequestInterface $paymentRequest, OrderItemInterface $orderItem): ?string
    {
        $itemName = $this->buildItemName($orderItem);

        return sprintf('%sx - %s', $orderItem->getQuantity(), $itemName);
    }

    protected function buildItemName(OrderItemInterface $orderItem): string
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
