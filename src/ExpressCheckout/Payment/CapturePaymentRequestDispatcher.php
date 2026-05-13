<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Payment;

use Doctrine\ORM\EntityManagerInterface;
use FluxSE\SyliusStripePlugin\Command\WebElements\CapturePaymentRequest;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Factory\PaymentRequestFactoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Owns the Sylius+Stripe contract for kicking off a PaymentIntent capture from the
 * Express Checkout Element:
 * - The Web Elements Capture command is dispatched even when the resolved
 *   PaymentMethod is a stripe_checkout gateway — Express Checkout always goes
 *   through the PaymentIntent stack regardless of the configured gateway.
 */
final readonly class CapturePaymentRequestDispatcher implements CapturePaymentRequestDispatcherInterface
{
    /** @param PaymentRequestFactoryInterface<PaymentRequestInterface> $paymentRequestFactory */
    public function __construct(
        private PaymentRequestFactoryInterface $paymentRequestFactory,
        private MessageBusInterface $paymentRequestCommandBus,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function dispatch(PaymentInterface $payment, PaymentMethodInterface $paymentMethod): PaymentRequestInterface
    {
        $paymentRequest = $this->paymentRequestFactory->create($payment, $paymentMethod);
        $paymentRequest->setAction(PaymentRequestInterface::ACTION_CAPTURE);

        $this->entityManager->persist($paymentRequest);
        $this->paymentRequestCommandBus->dispatch(new CapturePaymentRequest($paymentRequest->getId()));
        $this->entityManager->flush();

        return $paymentRequest;
    }
}
