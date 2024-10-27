<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout;

use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class ShippingLineItemProvider implements ShippingLineItemProviderInterface
{
    public function __construct(
        private ShippingLineItemNameProviderInterface $shippingLineItemProvider,
    ) {
    }

    public function getLineItem(PaymentRequestInterface $paymentRequest, ShipmentInterface $shipment): ?array
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();
        if (null === $order) {
            return null;
        }

        $shippingTotal = $this->getShippingTotal($shipment);
        if (0 === $shippingTotal) {
            return null;
        }

        $priceData = [
            'unit_amount' => $shippingTotal,
            'currency' => $order->getCurrencyCode(),
            'product_data' => [
                'name' => $this->shippingLineItemProvider->getItemName($paymentRequest, $shipment),
            ],
        ];

        return [
            'price_data' => $priceData,
            'quantity' => 1,
        ];
    }

    protected function getShippingTotal(ShipmentInterface $shipment): int
    {
        $shippingTotal = $shipment->getAdjustmentsTotal(AdjustmentInterface::SHIPPING_ADJUSTMENT);
        $shippingTotal += $shipment->getAdjustmentsTotal(AdjustmentInterface::ORDER_SHIPPING_PROMOTION_ADJUSTMENT);
        $shippingTotal += $shipment->getAdjustmentsTotal(AdjustmentInterface::TAX_ADJUSTMENT);

        return $shippingTotal;
    }
}
