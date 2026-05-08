<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Checkout;

use FluxSE\SyliusStripePlugin\Manager\WebElements\RetrieveManagerInterface as PaymentIntentRetrieveManagerInterface;
use Stripe\ApiResource;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class SubscriptionAwareRetrieveManager implements RetrieveManagerInterface
{
    public function __construct(
        private RetrieveManagerInterface $inner,
        private PaymentIntentRetrieveManagerInterface $paymentIntentManager,
    ) {
    }

    public function retrieve(PaymentRequestInterface $paymentRequest, string $id): ApiResource
    {
        $session = $this->inner->retrieve($paymentRequest, $id);

        if (Session::MODE_SUBSCRIPTION !== $session->mode) {
            return $session;
        }

        $this->enrichPaymentIntents($session, $paymentRequest);

        return $session;
    }

    private function enrichPaymentIntents(Session $session, PaymentRequestInterface $paymentRequest): void
    {
        $invoice = $session->invoice;
        if (false === $invoice instanceof Invoice) {
            return;
        }

        $payments = $invoice->payments;
        if (null === $payments) {
            return;
        }

        foreach ($payments->data as $invoicePayment) {
            /** @var StripeObject|null $payment */
            $payment = $invoicePayment->payment ?? null;
            if (null === $payment) {
                continue;
            }

            if ('payment_intent' !== ($payment['type'] ?? null)) {
                continue;
            }

            $paymentIntentReference = $payment['payment_intent'] ?? null;
            if (false === is_string($paymentIntentReference)) {
                continue;
            }

            $paymentIntent = $this->paymentIntentManager->retrieve($paymentRequest, $paymentIntentReference);
            $payment['payment_intent'] = $paymentIntent;
        }
    }
}
