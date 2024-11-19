<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\OrderItemLineItemProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class OrderItemLineItemProvider implements OrderItemLineItemProviderInterface
{
    /**
     * @param OrderItemLineItemProviderInterface[] $orderItemLineItemProviders
     */
    public function __construct(
        private iterable $orderItemLineItemProviders,
    ) {
    }

    public function getDetails(
        PaymentRequestInterface $paymentRequest,
        OrderItemInterface $orderItem,
        array &$details
    ): void {
        /** @var OrderInterface|null $order */
        $order = $orderItem->getOrder();

        if (null === $order) {
            return;
        }

        $priceData = [
            'unit_amount' => $orderItem->getTotal(),
            'currency' => $order->getCurrencyCode(),
        ];

        $lineItem = [
            'price_data' => $priceData,
            'quantity' => 1,
        ];

        foreach ($this->orderItemLineItemProviders as $orderItemLineItemProvider) {
            $orderItemLineItemProvider->getDetails($paymentRequest, $orderItem, $lineItem);
        }

        $details[] = $lineItem;
    }
}
