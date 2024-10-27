<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class LineItemProvider implements LineItemProviderInterface
{
    public function __construct(
        private LineItemImagesProviderInterface $lineItemImagesProvider,
        private LinetItemNameProviderInterface $lineItemNameProvider,
    ) {
    }

    public function getLineItem(PaymentRequestInterface $paymentRequest, OrderItemInterface $orderItem): ?array
    {
        /** @var OrderInterface|null $order */
        $order = $orderItem->getOrder();

        if (null === $order) {
            return null;
        }

        $priceData = [
            'unit_amount' => $orderItem->getTotal(),
            'currency' => $order->getCurrencyCode(),
            'product_data' => [
                'name' => $this->lineItemNameProvider->getItemName($paymentRequest, $orderItem),
                'images' => $this->lineItemImagesProvider->getImageUrls($paymentRequest, $orderItem),
            ],
        ];

        return [
            'price_data' => $priceData,
            'quantity' => 1,
        ];
    }
}
