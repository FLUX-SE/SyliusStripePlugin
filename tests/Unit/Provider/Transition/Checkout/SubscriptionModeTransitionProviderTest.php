<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider\Transition\Checkout;

use FluxSE\SyliusStripePlugin\Provider\Transition\Checkout\SubscriptionModeTransitionProvider;
use FluxSE\SyliusStripePlugin\Provider\Transition\WebElements\PaymentIntentTransitionProvider;
use PHPUnit\Framework\TestCase;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use Stripe\PaymentIntent;

final class SubscriptionModeTransitionProviderTest extends TestCase
{
    private SubscriptionModeTransitionProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new SubscriptionModeTransitionProvider(new PaymentIntentTransitionProvider());
    }

    /**
     * @dataProvider authorizeDataProvider
     */
    public function test_is_authorize(string $paymentIntentStatus, bool $expectedResult): void
    {
        $session = $this->createSessionWithPaymentIntent($paymentIntentStatus);

        $result = $this->provider->isAuthorize($session);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function authorizeDataProvider(): iterable
    {
        yield 'payment intent requires capture' => [PaymentIntent::STATUS_REQUIRES_CAPTURE, true];
        yield 'payment intent succeeded' => [PaymentIntent::STATUS_SUCCEEDED, false];
        yield 'payment intent processing' => [PaymentIntent::STATUS_PROCESSING, false];
        yield 'payment intent canceled' => [PaymentIntent::STATUS_CANCELED, false];
    }

    /**
     * @dataProvider completeDataProvider
     */
    public function test_is_complete(string $paymentIntentStatus, string $sessionPaymentStatus, bool $chargeRefunded, bool $expectedResult): void
    {
        $session = $this->createSessionWithPaymentIntent($paymentIntentStatus, $sessionPaymentStatus, null, $chargeRefunded);

        $result = $this->provider->isComplete($session);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return iterable<string, array{string, string, bool, bool}>
     */
    public static function completeDataProvider(): iterable
    {
        yield 'succeeded with paid status and not refunded' => [PaymentIntent::STATUS_SUCCEEDED, Session::PAYMENT_STATUS_PAID, false, true];
        yield 'succeeded with paid status but refunded' => [PaymentIntent::STATUS_SUCCEEDED, Session::PAYMENT_STATUS_PAID, true, false];
        yield 'succeeded with unpaid status' => [PaymentIntent::STATUS_SUCCEEDED, Session::PAYMENT_STATUS_UNPAID, false, false];
        yield 'processing with paid status' => [PaymentIntent::STATUS_PROCESSING, Session::PAYMENT_STATUS_PAID, false, false];
        yield 'requires capture with paid status' => [PaymentIntent::STATUS_REQUIRES_CAPTURE, Session::PAYMENT_STATUS_PAID, false, false];
    }

    public function test_is_fail_always_returns_false(): void
    {
        $session = $this->createSessionWithPaymentIntent(PaymentIntent::STATUS_SUCCEEDED);

        $result = $this->provider->isFail($session);

        self::assertFalse($result);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function test_is_process(string $paymentIntentStatus, string $sessionPaymentStatus, bool $expectedResult): void
    {
        $session = $this->createSessionWithPaymentIntent($paymentIntentStatus, $sessionPaymentStatus);

        $result = $this->provider->isProcess($session);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function processDataProvider(): iterable
    {
        yield 'processing with unpaid status' => [PaymentIntent::STATUS_PROCESSING, Session::PAYMENT_STATUS_UNPAID, true];
        yield 'processing with paid status' => [PaymentIntent::STATUS_PROCESSING, Session::PAYMENT_STATUS_PAID, false];
        yield 'succeeded with unpaid status' => [PaymentIntent::STATUS_SUCCEEDED, Session::PAYMENT_STATUS_UNPAID, false];
    }

    /**
     * @dataProvider cancelDataProvider
     *
     * @param array<string, mixed>|null $lastPaymentError
     */
    public function test_is_cancel(string $paymentIntentStatus, ?array $lastPaymentError, bool $expectedResult): void
    {
        $session = $this->createSessionWithPaymentIntent($paymentIntentStatus, Session::PAYMENT_STATUS_UNPAID, $lastPaymentError);

        $result = $this->provider->isCancel($session);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>|null, bool}>
     */
    public static function cancelDataProvider(): iterable
    {
        yield 'canceled status' => [PaymentIntent::STATUS_CANCELED, null, true];
        yield 'requires payment method with error' => [PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD, ['code' => 'card_declined'], true];
        yield 'requires payment method without error' => [PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD, null, false];
        yield 'succeeded status' => [PaymentIntent::STATUS_SUCCEEDED, null, false];
    }

    /**
     * @dataProvider refundDataProvider
     */
    public function test_is_refund(string $paymentIntentStatus, string $sessionPaymentStatus, bool $chargeRefunded, bool $expectedResult): void
    {
        $session = $this->createSessionWithPaymentIntent(
            $paymentIntentStatus,
            $sessionPaymentStatus,
            null,
            $chargeRefunded
        );

        $result = $this->provider->isRefund($session);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return iterable<string, array{string, string, bool, bool}>
     */
    public static function refundDataProvider(): iterable
    {
        yield 'succeeded paid and refunded' => [PaymentIntent::STATUS_SUCCEEDED, Session::PAYMENT_STATUS_PAID, true, true];
        yield 'succeeded paid not refunded' => [PaymentIntent::STATUS_SUCCEEDED, Session::PAYMENT_STATUS_PAID, false, false];
        yield 'succeeded unpaid and refunded' => [PaymentIntent::STATUS_SUCCEEDED, Session::PAYMENT_STATUS_UNPAID, true, false];
        yield 'processing paid and refunded' => [PaymentIntent::STATUS_PROCESSING, Session::PAYMENT_STATUS_PAID, true, false];
    }

    public function test_it_throws_exception_when_session_mode_is_not_subscription(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_PAYMENT,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/only able to provide "subscription" Checkout Session mode/');

        $this->provider->isAuthorize($session);
    }

    public function test_it_throws_exception_when_invoice_is_not_expanded(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'invoice' => 'in_test_1', // String instead of object
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/need to get access to an Invoice object/');

        $this->provider->isAuthorize($session);
    }

    public function test_it_throws_exception_when_payment_intent_is_not_expanded(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => Session::PAYMENT_STATUS_PAID,
            'invoice' => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
                'payment_intent' => 'pi_test_1', // String instead of object
            ],
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/need to get access to a PaymentIntent object/');

        $this->provider->isAuthorize($session);
    }

    public function test_get_supported_mode_returns_subscription(): void
    {
        $mode = SubscriptionModeTransitionProvider::getSupportedMode();

        self::assertSame(Session::MODE_SUBSCRIPTION, $mode);
    }

    public function test_is_charge_refunded_returns_false_when_charge_is_null(): void
    {
        $session = $this->createSessionWithPaymentIntent(PaymentIntent::STATUS_SUCCEEDED, Session::PAYMENT_STATUS_PAID, null, false, false);

        $result = $this->provider->isRefund($session);

        self::assertFalse($result);
    }

    /**
     * @param array<string, mixed>|null $lastPaymentError
     */
    private function createSessionWithPaymentIntent(
        string $paymentIntentStatus,
        string $sessionPaymentStatus = Session::PAYMENT_STATUS_PAID,
        ?array $lastPaymentError = null,
        bool $chargeRefunded = false,
        bool $includeCharge = true
    ): Session {
        $paymentIntentData = [
            'id' => 'pi_test_1',
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => $paymentIntentStatus,
        ];

        if ($lastPaymentError !== null) {
            $paymentIntentData['last_payment_error'] = $lastPaymentError;
        }

        if ($includeCharge && ($chargeRefunded || $paymentIntentStatus === PaymentIntent::STATUS_SUCCEEDED)) {
            $paymentIntentData['latest_charge'] = [
                'id' => 'ch_test_1',
                'object' => Charge::OBJECT_NAME,
                'refunded' => $chargeRefunded,
            ];
        }

        return Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'status' => Session::STATUS_COMPLETE,
            'payment_status' => $sessionPaymentStatus,
            'invoice' => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
                'payment_intent' => $paymentIntentData,
            ],
        ]);
    }
}

