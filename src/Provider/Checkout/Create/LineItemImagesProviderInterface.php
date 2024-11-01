<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface LineItemImagesProviderInterface
{
    /**
     * @return string[]
     */
    public function getImageUrls(PaymentRequestInterface $paymentRequest, OrderItemInterface $orderItem): array;

    public function getImageUrlFromProduct(PaymentRequestInterface $paymentRequest, ProductInterface $product): string;
}
