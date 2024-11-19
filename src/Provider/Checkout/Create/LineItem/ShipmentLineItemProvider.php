<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\ShipmentLineItemProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class ShipmentLineItemProvider implements ShipmentLineItemProviderInterface
{
    /**
     * @param ShipmentLineItemProviderInterface[] $shipmentLineItemProviders
     */
    public function __construct(
        private iterable $shipmentLineItemProviders,
    ) {
    }

    public function getDetails(
        PaymentRequestInterface $paymentRequest,
        ShipmentInterface $shipment,
        array &$details
    ): void {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();

        $order = $payment->getOrder();
        if (null === $order) {
            return;
        }

        $shippingTotal = $shipment->getAdjustmentsTotal();
        if (0 === $shippingTotal) {
            return;
        }

        $priceData = [
            'unit_amount' => $shippingTotal,
            'currency' => $order->getCurrencyCode(),
        ];

        $lineItem = [
            'price_data' => $priceData,
            'quantity' => 1,
        ];


        foreach ($this->shipmentLineItemProviders as $shipmentLineItemProvider) {
            $shipmentLineItemProvider->getDetails($paymentRequest, $shipment, $lineItem);
        }

        $details[] = $lineItem;
    }
}
