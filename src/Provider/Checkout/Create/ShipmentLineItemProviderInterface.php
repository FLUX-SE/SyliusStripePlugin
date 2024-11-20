<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use Stripe\ApiResource;
use Stripe\LineItem;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as StripeObject
 */
interface ShipmentLineItemProviderInterface
{
    /**
     * @param array<key-of<T>, mixed> $params
     */
    public function provideFromShipment(
        ShipmentInterface $shipment,
        PaymentRequestInterface $paymentRequest,
        array &$params
    ): void;
}
