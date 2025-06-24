<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Refund;

use FluxSE\SyliusStripePlugin\Manager\AllManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class RefundSubscriptionInitProvider implements PaymentIntentToRefundProviderInterface
{
    public function __construct(
        private AllManagerInterface $allManager,
    ) {
    }

    public function provide(PaymentRequestInterface $paymentRequest): null|string|PaymentIntent
    {
        /** @var string|null $object */
        $object = $paymentRequest->getPayment()->getDetails()['object'] ?? null;
        if (Session::OBJECT_NAME !== $object) {
            return null;
        }

        /** @var string|null $mode */
        $mode = $paymentRequest->getPayment()->getDetails()['mode'] ?? null;
        if (Session::MODE_SUBSCRIPTION !== $mode) {
            return null;
        }

        $invoices = $this->allManager->all($paymentRequest);

        /** @var Invoice|null $invoice */
        $invoice = $invoices->first();

        return $invoice?->payment_intent;
    }
}
