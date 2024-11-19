<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use FluxSE\SyliusStripePlugin\Provider\DetailsProviderInterface;
use Stripe\Checkout\Session;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<Session>
 */
final readonly class LineItemsProvider implements DetailsProviderInterface
{
    /**
     * @param OrderItemLineItemProviderInterface[] $orderItemLineItemProviders
     * @param ShipmentLineItemProviderInterface[] $shippingDetailsProviders
     */
    public function __construct(
        private iterable $orderItemLineItemProviders,
        private iterable $shippingDetailsProviders,
    ) {
    }

    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();
        if (null === $order) {
            return;
        }

        $lineItems = [];
        foreach ($order->getItems() as $orderItem) {
            foreach ($this->orderItemLineItemProviders as $orderItemLineItemProvider) {
                $orderItemLineItemProvider->getDetails($paymentRequest, $orderItem, $lineItems);
            }
        }

        foreach ($order->getShipments() as $shipment) {
            foreach ($this->shippingDetailsProviders as $shippingDetailsProvider) {
                $shippingDetailsProvider->getDetails($paymentRequest, $shipment, $lineItems);
            }
        }

        if ([] === $lineItems) {
            return;
        }

        if (false === isset($details['line_items'])) {
            $details['line_items'] = [];
        }

        $details['line_items'] += $lineItems;
    }
}
