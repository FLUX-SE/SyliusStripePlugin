<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\OrderItemLineItemProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\ShipmentLineItemProviderInterface;
use Stripe\LineItem;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements OrderItemLineItemProviderInterface<LineItem>
 * @implements ShipmentLineItemProviderInterface<LineItem>
 */
final class CurrencyProvider implements OrderItemLineItemProviderInterface, ShipmentLineItemProviderInterface
{
    public function provideFromOrderItem(
        OrderItemInterface $orderItem,
        PaymentRequestInterface $paymentRequest,
        array &$params,
    ): void {
        $this->provide($paymentRequest, $params);
    }

    public function provideFromShipment(
        ShipmentInterface $shipment,
        PaymentRequestInterface $paymentRequest,
        array &$params,
    ): void {
        $this->provide($paymentRequest, $params);
    }

    /**
     * @param array<key-of<StripeObject>, mixed> $params
     */
    private function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        $currencyCode = $paymentRequest->getPayment()->getCurrencyCode();
        if (null === $currencyCode) {
            return;
        }

        $params['currency'] = $currencyCode;
    }
}
