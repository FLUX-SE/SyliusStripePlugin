<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandProvider\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\CancelAuthorizedPaymentRequest;
use FluxSE\SyliusStripePlugin\Command\Checkout\ExpirePaymentRequest;
use Stripe\PaymentIntent;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class CancelPaymentRequestCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return true;
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        if ($this->isCaptureMethodManualAndRequireCapture($paymentRequest)) {
            return new CancelAuthorizedPaymentRequest($paymentRequest->getId());
        }

        return new ExpirePaymentRequest($paymentRequest->getId());
    }

    private function isCaptureMethodManualAndRequireCapture(PaymentRequestInterface $paymentRequest): bool
    {
        /**
         * @var array{
         *     payment_intent?: array{
         *          capture_method?: string,
         *          status?: string,
         *      }
         * } $details
         */
        $details = $paymentRequest->getPayment()->getDetails();
        if (($details['payment_intent']['capture_method'] ?? '') !== PaymentIntent::CAPTURE_METHOD_MANUAL) {
            return false;
        }

        return ($details['payment_intent']['status'] ?? '') === PaymentIntent::STATUS_REQUIRES_CAPTURE;
    }
}
