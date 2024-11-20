<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\InnerParamsProviderInterface;
use Stripe\Checkout\Session;
use Stripe\LineItem;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements InnerParamsProviderInterface<Session>
 */
final readonly class LineItemsProvider implements InnerParamsProviderInterface
{
    /**
     * @param OrderItemLineItemProviderInterface<LineItem>[] $orderItemLineItemProviders
     * @param ShipmentLineItemProviderInterface<LineItem>[] $shippingDetailsProviders
     */
    public function __construct(
        private iterable $orderItemLineItemProviders,
        private iterable $shippingDetailsProviders,
    ) {
    }

    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();
        if (null === $order) {
            return;
        }

        /** @var array<array-key, array<key-of<LineItem>, mixed>> $lineItems */
        $lineItems = [];
        foreach ($order->getItems() as $orderItem) {
            foreach ($this->orderItemLineItemProviders as $orderItemLineItemProvider) {
                $orderItemLineItemProvider->provideFromOrderItem($orderItem, $paymentRequest, $lineItems);
            }
        }

        foreach ($order->getShipments() as $shipment) {
            foreach ($this->shippingDetailsProviders as $shippingDetailsProvider) {
                $shippingDetailsProvider->provideFromShipment($shipment, $paymentRequest, $lineItems);
            }
        }

        if ([] === $lineItems) {
            return;
        }

        if (false === isset($params['line_items'])) {
            $params['line_items'] = [];
        }

        $params['line_items'] += $lineItems;
    }
}
