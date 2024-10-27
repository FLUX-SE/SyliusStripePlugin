<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class LineItemsProvider implements LineItemsProviderInterface
{
    public function __construct(
        private LineItemProviderInterface $lineItemProvider,
        private ShippingLineItemProviderInterface $shippingLineItemProvider,
    ) {
    }

    public function getLineItems(PaymentRequestInterface $paymentRequest): ?array
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();
        if (null === $order) {
            return null;
        }

        $lineItems = [];
        foreach ($order->getItems() as $orderItem) {
            $lineItem = $this->lineItemProvider->getLineItem($paymentRequest, $orderItem);
            if (null !== $lineItem) {
                $lineItems[] = $lineItem;
            }
        }

        foreach ($order->getShipments() as $shipment) {
            $lineItem = $this->shippingLineItemProvider->getLineItem($paymentRequest, $shipment);
            if (null !== $lineItem) {
                $lineItems[] = $lineItem;
            }
        }

        return $lineItems;
    }
}
