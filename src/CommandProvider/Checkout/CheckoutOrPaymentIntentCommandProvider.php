<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandProvider\Checkout;

use Stripe\PaymentIntent;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * Routes the captured / status / notify / cancel / refund command for a stripe_checkout
 * PaymentMethod to the Web Elements command provider when the PaymentRequest's
 * payment.details actually holds a Stripe PaymentIntent — Express Checkout on the cart
 * page always creates a PaymentIntent regardless of the configured gateway, and the
 * Checkout pipeline would otherwise try to look up a Checkout Session by PaymentIntent id.
 */
final readonly class CheckoutOrPaymentIntentCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function __construct(
        private PaymentRequestCommandProviderInterface $checkoutCommandProvider,
        private PaymentRequestCommandProviderInterface $webElementsCommandProvider,
    ) {
    }

    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return $this->pickProvider($paymentRequest)->supports($paymentRequest);
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        return $this->pickProvider($paymentRequest)->provide($paymentRequest);
    }

    private function pickProvider(PaymentRequestInterface $paymentRequest): PaymentRequestCommandProviderInterface
    {
        $object = $paymentRequest->getPayment()->getDetails()['object'] ?? null;
        if (PaymentIntent::OBJECT_NAME === $object) {
            return $this->webElementsCommandProvider;
        }

        return $this->checkoutCommandProvider;
    }
}
