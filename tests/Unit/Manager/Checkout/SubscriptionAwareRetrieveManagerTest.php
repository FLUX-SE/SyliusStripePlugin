<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Manager\Checkout;

use FluxSE\SyliusStripePlugin\Manager\Checkout\RetrieveManagerInterface;
use FluxSE\SyliusStripePlugin\Manager\Checkout\SubscriptionAwareRetrieveManager;
use FluxSE\SyliusStripePlugin\Manager\WebElements\RetrieveManagerInterface as PaymentIntentRetrieveManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class SubscriptionAwareRetrieveManagerTest extends TestCase
{
    private RetrieveManagerInterface&MockObject $inner;

    private PaymentIntentRetrieveManagerInterface&MockObject $paymentIntentManager;

    private SubscriptionAwareRetrieveManager $manager;

    private PaymentRequestInterface&MockObject $paymentRequest;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(RetrieveManagerInterface::class);
        $this->paymentIntentManager = $this->createMock(PaymentIntentRetrieveManagerInterface::class);
        $this->manager = new SubscriptionAwareRetrieveManager($this->inner, $this->paymentIntentManager);
        $this->paymentRequest = $this->createMock(PaymentRequestInterface::class);
    }

    public function test_payment_mode_skips_payment_intent_enrichment(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_PAYMENT,
        ]);

        $this->inner->method('retrieve')->willReturn($session);
        $this->paymentIntentManager->expects(self::never())->method('retrieve');

        $result = $this->manager->retrieve($this->paymentRequest, 'cs_test_1');

        self::assertSame($session, $result);
    }

    public function test_subscription_mode_without_invoice_skips_enrichment(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'invoice' => 'in_test_1',
        ]);

        $this->inner->method('retrieve')->willReturn($session);
        $this->paymentIntentManager->expects(self::never())->method('retrieve');

        $this->manager->retrieve($this->paymentRequest, 'cs_test_1');
    }

    public function test_subscription_mode_with_null_payments_skips_enrichment(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'invoice' => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
            ],
        ]);

        $this->inner->method('retrieve')->willReturn($session);
        $this->paymentIntentManager->expects(self::never())->method('retrieve');

        $this->manager->retrieve($this->paymentRequest, 'cs_test_1');
    }

    public function test_subscription_mode_with_empty_payments_data_skips_enrichment(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'invoice' => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
                'payments' => [
                    'object' => 'list',
                    'data' => [],
                    'has_more' => false,
                ],
            ],
        ]);

        $this->inner->method('retrieve')->willReturn($session);
        $this->paymentIntentManager->expects(self::never())->method('retrieve');

        $this->manager->retrieve($this->paymentRequest, 'cs_test_1');
    }

    public function test_subscription_mode_with_already_expanded_payment_intent_skips_enrichment(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'invoice' => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
                'payments' => [
                    'object' => 'list',
                    'data' => [[
                        'id' => 'inpay_test_1',
                        'object' => 'invoice_payment',
                        'payment' => [
                            'type' => 'payment_intent',
                            'payment_intent' => [
                                'id' => 'pi_test_1',
                                'object' => PaymentIntent::OBJECT_NAME,
                            ],
                        ],
                    ]],
                    'has_more' => false,
                ],
            ],
        ]);

        $this->inner->method('retrieve')->willReturn($session);
        $this->paymentIntentManager->expects(self::never())->method('retrieve');

        $this->manager->retrieve($this->paymentRequest, 'cs_test_1');
    }

    public function test_subscription_mode_with_string_payment_intent_triggers_enrichment(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'invoice' => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
                'payments' => [
                    'object' => 'list',
                    'data' => [[
                        'id' => 'inpay_test_1',
                        'object' => 'invoice_payment',
                        'payment' => [
                            'type' => 'payment_intent',
                            'payment_intent' => 'pi_test_1',
                        ],
                    ]],
                    'has_more' => false,
                ],
            ],
        ]);

        $resolvedPaymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_test_1',
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => PaymentIntent::STATUS_SUCCEEDED,
        ]);

        $this->inner->method('retrieve')->willReturn($session);
        $this->paymentIntentManager
            ->expects(self::once())
            ->method('retrieve')
            ->with($this->paymentRequest, 'pi_test_1')
            ->willReturn($resolvedPaymentIntent);

        $result = $this->manager->retrieve($this->paymentRequest, 'cs_test_1');
        self::assertSame($session, $result);

        $invoice = $session->invoice;
        self::assertInstanceOf(Invoice::class, $invoice);
        $payments = $invoice->payments;
        self::assertNotNull($payments);
        $payment = $payments->data[0]->payment;
        self::assertSame($resolvedPaymentIntent, $payment['payment_intent']);
    }

    public function test_subscription_mode_with_non_payment_intent_type_skips_enrichment(): void
    {
        $session = Session::constructFrom([
            'id' => 'cs_test_1',
            'object' => Session::OBJECT_NAME,
            'mode' => Session::MODE_SUBSCRIPTION,
            'invoice' => [
                'id' => 'in_test_1',
                'object' => Invoice::OBJECT_NAME,
                'payments' => [
                    'object' => 'list',
                    'data' => [[
                        'id' => 'inpay_test_1',
                        'object' => 'invoice_payment',
                        'payment' => [
                            'type' => 'out_of_band_payment',
                        ],
                    ]],
                    'has_more' => false,
                ],
            ],
        ]);

        $this->inner->method('retrieve')->willReturn($session);
        $this->paymentIntentManager->expects(self::never())->method('retrieve');

        $this->manager->retrieve($this->paymentRequest, 'cs_test_1');
    }
}
