<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup;

use Doctrine\Persistence\ObjectManager;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeWebElementsMocker;

class ManagingStripeWebElementsOrdersContext implements ManagingStripeOrdersContextInterface
{
    public function __construct(
        private StateMachineInterface $stateMachine,
        private ObjectManager $objectManager,
        private readonly StripeWebElementsMocker $stripeWebElementsMocker,
    ) {
    }

    /**
     * @Given /^(this order) is already paid using Stripe web elements$/
     */
    public function thisOrderIsAlreadyPaidUsingStripe(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => PaymentIntent::OBJECT_NAME,
            'id' => 'pi_test_1',
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
        ];
        $payment->setDetails($details);

        $this->stateMachine->apply(
            $payment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_COMPLETE,
        );

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this order) is already authorized using Stripe web elements$/
     */
    public function thisOrderIsAlreadyAuthorizedUsingStripe(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => PaymentIntent::OBJECT_NAME,
            'id' => 'pi_test_1',
            'status' => PaymentIntent::STATUS_REQUIRES_CAPTURE,
            'capture_method' => PaymentIntent::CAPTURE_METHOD_MANUAL,
        ];
        $payment->setDetails($details);

        $this->stateMachine->apply(
            $payment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_AUTHORIZE,
        );

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this order) is not yet paid using Stripe web elements$/
     */
    public function thisOrderIsNotYetPaidUsingStripe(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => PaymentIntent::OBJECT_NAME,
            'id' => 'pi_test_1',
            'status' => PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
        ];
        $payment->setDetails($details);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this order) payment has been canceled$/
     */
    public function thisOrderPaymentHasBeenCancelled(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $this->stateMachine->apply(
            $payment,
            PaymentTransitions::GRAPH,
            PaymentTransitions::TRANSITION_CANCEL,
        );

        $this->objectManager->flush();
    }

    /**
     * @Given /^I am prepared to cancel (this order)$/
     */
    public function iAmPreparedToCancelThisOrder(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment(BasePaymentInterface::STATE_NEW);
        $details = $payment->getDetails();

        $this->stripeWebElementsMocker->mockCancelPayment($details['capture_method']);
    }

    /**
     * @Given /^I am prepared to capture authorization of (this order)$/
     */
    public function iAmPreparedToCaptureAuthorizationOfThisOrder(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment(BasePaymentInterface::STATE_AUTHORIZED);
        $details = $payment->getDetails();

        $this->stripeWebElementsMocker->mockCompleteAuthorized(
            $details['status'],
            $details['capture_method'],
        );
    }

    /**
     * @Given /^I am prepared to cancel authorization on (this order)$/
     */
    public function iAmPreparedToCancelAuthorizationOnThisOrder(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment(BasePaymentInterface::STATE_AUTHORIZED);
        $details = $payment->getDetails();

        if ([] === $details) {
            return;
        }

        $this->stripeWebElementsMocker->mockCancelPayment(
            $details['capture_method'],
        );
    }

    /**
     * @Given I am prepared to refund this order
     */
    public function iAmPreparedToRefundThisOrder(): void
    {
        $this->stripeWebElementsMocker->mockRefundPayment();
    }
}
