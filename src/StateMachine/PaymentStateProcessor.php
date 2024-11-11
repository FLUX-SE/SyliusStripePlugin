<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\StateMachine;

use Sylius\Bundle\PaymentBundle\Announcer\PaymentRequestAnnouncerInterface;
use Sylius\Bundle\PaymentBundle\Checker\FinalizedPaymentRequestCheckerInterface;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Factory\PaymentRequestFactoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class PaymentStateProcessor implements PaymentStateProcessorInterface
{
    /**
     * @param PaymentRequestFactoryInterface<PaymentRequestInterface> $paymentRequestFactory
     * @param PaymentRequestRepositoryInterface<PaymentRequestInterface> $paymentRequestRepository
     * @param string[] $supportedFactories
     * @param string[] $allowedPaymentFromStates
     */
    public function __construct(
        private GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        private FinalizedPaymentRequestCheckerInterface $finalizedPaymentRequestChecker,
        private PaymentRequestFactoryInterface $paymentRequestFactory,
        private PaymentRequestRepositoryInterface $paymentRequestRepository,
        private PaymentRequestAnnouncerInterface $paymentRequestAnnouncer,
        private array $supportedFactories,
        private array $allowedPaymentFromStates,
        private string $requiredPaymentState,
        private string $paymentRequestAction,
    ) {
    }

    public function __invoke(PaymentInterface $payment, string $fromState): void
    {
        if (
            $this->allowedPaymentFromStates !== []
            && false === in_array($fromState, $this->allowedPaymentFromStates, true
        )) {
            return;
        }

        $paymentMethod = $payment->getMethod();
        if (null === $paymentMethod) {
            return;
        }

        $factoryName = $this->gatewayFactoryNameProvider->provide($paymentMethod);
        if (false === in_array($factoryName, $this->supportedFactories, true)) {
            return;
        }

        Assert::eq(
            $payment->getState(),
            $this->requiredPaymentState,
            sprintf(
                'The payment must have state "%s" at this point, found "%s".',
                $this->requiredPaymentState,
                $payment->getState()
            )
        );

        $paymentRequest = $this->paymentRequestRepository->findOneByActionPaymentAndMethod(
            $this->paymentRequestAction,
            $payment,
            $paymentMethod
        );

        if ( null === $paymentRequest  || $this->finalizedPaymentRequestChecker->isFinal($paymentRequest)) {
            $paymentRequest = $this->paymentRequestFactory->create($payment, $paymentMethod);
            $paymentRequest->setAction($this->paymentRequestAction);

            $this->paymentRequestRepository->add($paymentRequest);
        }

        $this->paymentRequestAnnouncer->dispatchPaymentRequestCommand($paymentRequest);
    }
}
