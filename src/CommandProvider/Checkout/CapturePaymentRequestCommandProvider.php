<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\CommandProvider\Checkout;

use FluxSE\SyliusStripePlugin\Command\Checkout\CaptureEndPaymentRequest;
use FluxSE\SyliusStripePlugin\Command\Checkout\CapturePaymentRequest;
use FluxSE\SyliusStripePlugin\Command\Checkout\CompleteAuthorizedPaymentRequest;
use Stripe\PaymentIntent;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class CapturePaymentRequestCommandProvider implements PaymentRequestCommandProviderInterface
{
    public function supports(PaymentRequestInterface $paymentRequest): bool
    {
        return true;
    }

    public function provide(PaymentRequestInterface $paymentRequest): object
    {
        if (PaymentRequestInterface::STATE_PROCESSING === $paymentRequest->getState()) {
            return new CaptureEndPaymentRequest($paymentRequest->getId());
        }

        if ($this->isCaptureMethodManualAndRequireCapture($paymentRequest)) {
            return new CompleteAuthorizedPaymentRequest($paymentRequest->getId());
        }

        return new CapturePaymentRequest($paymentRequest->getId());
    }

    private function isCaptureMethodManualAndRequireCapture(PaymentRequestInterface $paymentRequest): bool
    {
        if (PaymentRequestInterface::ACTION_AUTHORIZE !== $paymentRequest->getAction()) {
            return false;
        }

        if (($paymentRequest->getPayment()->getDetails()['payment_intent']['capture_method'] ?? '') !== PaymentIntent::CAPTURE_METHOD_MANUAL) {
            return false;
        }

        return ($paymentRequest->getPayment()->getDetails()['payment_intent']['status'] ?? '') === PaymentIntent::STATUS_REQUIRES_CAPTURE;
    }
}
