<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor\Checkout;

use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * Used by the stripe_checkout webhook notify handler when the payment method may be settled
 * either through a Checkout Session (classic flow) or a PaymentIntent (Express Checkout on
 * the cart page reuses the Web Elements pipeline regardless of the configured gateway).
 *
 * Dispatches the transition based on the Stripe object stored in Payment.details.
 */
final readonly class CheckoutOrPaymentIntentTransitionProcessor implements PaymentTransitionProcessorInterface
{
    public function __construct(
        private PaymentTransitionProcessorInterface $sessionTransitionProcessor,
        private PaymentTransitionProcessorInterface $paymentIntentTransitionProcessor,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest): void
    {
        $object = $paymentRequest->getPayment()->getDetails()['object'] ?? null;

        if (Session::OBJECT_NAME === $object) {
            $this->sessionTransitionProcessor->process($paymentRequest);

            return;
        }

        if (PaymentIntent::OBJECT_NAME === $object) {
            $this->paymentIntentTransitionProcessor->process($paymentRequest);
        }
    }
}
