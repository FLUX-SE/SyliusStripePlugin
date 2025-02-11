<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup;

use Doctrine\Persistence\ObjectManager;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeCheckoutMocker;

class ManagingStripeCheckoutOrdersContext implements ManagingStripeOrdersContextInterface
{
    public function __construct(
        private StateMachineInterface $stateMachine,
        private ObjectManager $objectManager,
        private StripeCheckoutMocker $stripeCheckoutSessionMocker,
    ) {
    }

    /**
     * @Given /^(this order) is already paid using Stripe Checkout$/
     */
    public function thisOrderIsAlreadyPaidUsingStripe(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => Session::OBJECT_NAME,
            'id' => 'cs_test_1',
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'mode' => Session::MODE_PAYMENT,
            'payment_intent' => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_SUCCEEDED,
                'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
            ],
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
     * @Given /^(this order) is already authorized using Stripe Checkout$/
     */
    public function thisOrderIsAlreadyAuthorizedUsingStripe(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => Session::OBJECT_NAME,
            'id' => 'cs_test_1',
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_UNPAID,
            'mode' => Session::MODE_PAYMENT,
            'payment_intent' => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_REQUIRES_CAPTURE,
                'capture_method' => PaymentIntent::CAPTURE_METHOD_MANUAL,
            ],
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
     * @Given /^(this order) is not yet paid using Stripe Checkout$/
     */
    public function thisOrderIsNotYetPaidUsingStripe(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => Session::OBJECT_NAME,
            'id' => 'cs_test_1',
            'status' => Session::STATUS_OPEN,
            'payment_status' => Session::PAYMENT_STATUS_UNPAID,
            'mode' => Session::MODE_PAYMENT,
            'payment_intent' => [
                'id' => 'pi_test_1',
                'object' => PaymentIntent::OBJECT_NAME,
                'status' => PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
                'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
            ],
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
        $status = $details['payment_intent']['status'];
        $captureMethod = $details['payment_intent']['capture_method'];

        $this->stripeCheckoutSessionMocker->mockCancelPayment($status, $captureMethod);
    }

    /**
     * @Given /^I am prepared to capture authorization of (this order)$/
     */
    public function iAmPreparedToCaptureAuthorizationOfThisOrder(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment(BasePaymentInterface::STATE_AUTHORIZED);
        $details = $payment->getDetails();
        $status = $details['payment_intent']['status'];
        $captureMethod = $details['payment_intent']['capture_method'];

        $this->stripeCheckoutSessionMocker->mockCompleteAuthorized($status, $captureMethod);
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

        $this->stripeCheckoutSessionMocker->mockCancelPayment(
            $details['payment_intent']['status'],
            $details['payment_intent']['payment_status'],
        );
    }

    /**
     * @Given I am prepared to refund this order
     */
    public function iAmPreparedToRefundThisOrder(): void
    {
        $this->stripeCheckoutSessionMocker->mockRefundPayment();
    }

    /**
     * @Given /^I am prepared to expire (this order)$/
     */
    public function iAmPreparedToExpireThisOrder(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment(BasePaymentInterface::STATE_NEW);
        $details = $payment->getDetails();

        if ([] === $details) {
            return;
        }

        $this->stripeCheckoutSessionMocker->mockExpirePayment(
            $details['status'],
            $details['payment_status'],
        );
    }
}
