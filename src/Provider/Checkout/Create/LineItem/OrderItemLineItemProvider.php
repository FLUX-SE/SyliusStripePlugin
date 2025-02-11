<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\OrderItemLineItemProviderInterface;
use Stripe\LineItem;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements OrderItemLineItemProviderInterface<LineItem>
 */
final class OrderItemLineItemProvider implements OrderItemLineItemProviderInterface
{
    /**
     * @param OrderItemLineItemProviderInterface<LineItem>[] $orderItemLineItemProviders
     */
    public function __construct(
        private iterable $orderItemLineItemProviders,
    ) {
    }

    public function provideFromOrderItem(
        OrderItemInterface $orderItem,
        PaymentRequestInterface $paymentRequest,
        array &$params,
    ): void {
        /** @var array<key-of<LineItem>, mixed> $lineItem */
        $lineItem = [];

        foreach ($this->orderItemLineItemProviders as $orderItemLineItemProvider) {
            $orderItemLineItemProvider->provideFromOrderItem($orderItem, $paymentRequest, $lineItem);
        }

        if ([] === $lineItem) {
            return;
        }

        $params[] = $lineItem;
    }
}
