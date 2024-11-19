<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\Persistence\ObjectManager;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentInterface as BasePaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Tests\FluxSE\SyliusStripePlugin\Mocker\StripeCheckoutMocker;

class ManagingOrdersContext implements Context
{
    public function __construct(
        private StateMachineInterface $stateMachine,
        private ObjectManager $objectManager,
        private StripeCheckoutMocker $stripeCheckoutSessionMocker,
    ) {
    }

    /**
     * @Given /^(this order) is already paid as "([^"]+)" Stripe payment intent$/
     */
    public function thisOrderIsAlreadyPaid(OrderInterface $order, string $stripePaymentIntentId): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => PaymentIntent::OBJECT_NAME,
            'id' => $stripePaymentIntentId,
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
     * @Given /^(this order) is already authorized as "([^"]+)" Stripe payment intent$/
     */
    public function thisOrderIsAlreadyAuthorized(OrderInterface $order, string $stripePaymentIntentId): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => PaymentIntent::OBJECT_NAME,
            'id' => $stripePaymentIntentId,
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
     * @Given /^(this order) is not yet paid as "([^"]+)" Stripe Checkout Session$/
     */
    public function thisOrderIsNotYetPaidStripeCheckoutSession(OrderInterface $order, string $stripeCheckoutSessionId): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => Session::OBJECT_NAME,
            'id' => $stripeCheckoutSessionId,
            'status' => Session::STATUS_OPEN,
            'payment_status' => Session::PAYMENT_STATUS_UNPAID,
        ];
        $payment->setDetails($details);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this order) is not yet paid as "([^"]+)" Stripe JS$/
     */
    public function thisOrderIsNotYetPaidStripeJs(OrderInterface $order, string $stripePaymentIntentId): void
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment();

        $details = [
            'object' => PaymentIntent::OBJECT_NAME,
            'id' => $stripePaymentIntentId,
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
        $details = $this->getLastNewPaymentDetails($order);
        $status = $details['status'] ?? PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD;
        $captureMethod = $details['capture_method'] ?? PaymentIntent::CAPTURE_METHOD_AUTOMATIC;

        $this->stripeCheckoutSessionMocker->mockCancelPayment($status, $captureMethod);
    }

    /**
     * @Given /^I am prepared to expire (this order) checkout session$/
     */
    public function iAmPreparedToExpireThisOrderCheckoutSession(OrderInterface $order): void
    {
        $details = $this->getLastNewPaymentDetails($order);

        if ([] === $details) {
            return;
        }

        $this->stripeCheckoutSessionMocker->mockExpirePayment(
            $details['status'],
            $details['payment_status'],
        );
    }

    /**
     * @Given I am prepared to cancel the payment intent
     */
    public function iAmPreparedToExpireThePaymentIntentOnThisOrder(): void
    {
        $this->stripeCheckoutSessionMocker->mockCancelPayment(
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
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
     * @Given /^I am prepared to capture authorization of (this order)$/
     */
    public function iAmPreparedToCaptureAuthorizationOfThisOrder(OrderInterface $order): void
    {
        /** @var PaymentInterface $payment */
        $details = $this->getLastNewPaymentDetails($order);
        $status = $details['status'] ?? PaymentIntent::STATUS_REQUIRES_CAPTURE;
        $captureMethod = $details['capture_method'] ?? PaymentIntent::CAPTURE_METHOD_MANUAL;

        $this->stripeCheckoutSessionMocker->mockCaptureAuthorization($status, $captureMethod);
    }

    /**
     * @param OrderInterface $order
     * @return array<string, string>
     */
    private function getLastNewPaymentDetails(OrderInterface $order): array
    {
        /** @var PaymentInterface $payment */
        $payment = $order->getLastPayment(BasePaymentInterface::STATE_NEW);

        return $payment->getDetails();
    }
}
