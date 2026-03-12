<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\SyliusRefundPlugin\EventListener;

use Sylius\Bundle\PaymentBundle\Announcer\PaymentRequestAnnouncerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Payment\Factory\PaymentRequestFactoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Sylius\RefundPlugin\Event\RefundPaymentGenerated;

final readonly class RefundPaymentGeneratedEventListener
{
    /**
     * @param PaymentRepositoryInterface<PaymentInterface> $paymentRepository
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     * @param PaymentRequestFactoryInterface<PaymentRequestInterface> $paymentRequestFactory
     * @param PaymentRequestRepositoryInterface<PaymentRequestInterface> $paymentRequestRepository
     * @param string[] $supportedFactories
     */
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private PaymentRequestFactoryInterface $paymentRequestFactory,
        private PaymentRequestRepositoryInterface $paymentRequestRepository,
        private PaymentRequestAnnouncerInterface $paymentRequestAnnouncer,
        private array $supportedFactories,
    ) {
    }

    public function __invoke(RefundPaymentGenerated $event): void
    {
        $paymentMethod = $this->getPaymentMethod($event);
        if (null === $paymentMethod) {
            return;
        }

        if (false === $this->isStripePaymentMethod($paymentMethod)) {
            return;
        }

        $payment = $this->getPayment($event);
        if (null === $payment) {
            return;
        }

        $paymentRequest = $this->paymentRequestFactory->create($payment, $paymentMethod);
        $paymentRequest->setAction(PaymentRequestInterface::ACTION_REFUND);
        $paymentRequest->setPayload([
            'amount' => $event->amount(),
        ]);

        $this->paymentRequestRepository->add($paymentRequest);

        $this->paymentRequestAnnouncer->dispatchPaymentRequestCommand($paymentRequest);
    }

    private function getPayment(RefundPaymentGenerated $event): ?PaymentInterface
    {
        /** @var PaymentInterface|null $payment */
        $payment = $this->paymentRepository->find($event->paymentId());

        return $payment;
    }

    private function getPaymentMethod(RefundPaymentGenerated $event): ?PaymentMethodInterface
    {
        /** @var PaymentMethodInterface|null $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository->find($event->paymentMethodId());

        return $paymentMethod;
    }

    private function isStripePaymentMethod(PaymentMethodInterface $paymentMethod): bool
    {
        $factoryName = $paymentMethod->getGatewayConfig()?->getFactoryName();
        if (null === $factoryName) {
            return false;
        }

        return in_array($factoryName, $this->supportedFactories, true);
    }
}
