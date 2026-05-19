<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Processor\Checkout;

use FluxSE\SyliusStripePlugin\Processor\Checkout\CheckoutOrPaymentIntentTransitionProcessor;
use FluxSE\SyliusStripePlugin\Processor\PaymentTransitionProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class CheckoutOrPaymentIntentTransitionProcessorTest extends TestCase
{
    /** @var PaymentTransitionProcessorInterface&MockObject */
    private PaymentTransitionProcessorInterface $sessionProcessor;

    /** @var PaymentTransitionProcessorInterface&MockObject */
    private PaymentTransitionProcessorInterface $paymentIntentProcessor;

    private CheckoutOrPaymentIntentTransitionProcessor $processor;

    protected function setUp(): void
    {
        $this->sessionProcessor = $this->createMock(PaymentTransitionProcessorInterface::class);
        $this->paymentIntentProcessor = $this->createMock(PaymentTransitionProcessorInterface::class);

        $this->processor = new CheckoutOrPaymentIntentTransitionProcessor(
            $this->sessionProcessor,
            $this->paymentIntentProcessor,
        );
    }

    public function test_it_delegates_checkout_session_details_to_session_processor(): void
    {
        $paymentRequest = $this->createPaymentRequestWithDetails(['object' => Session::OBJECT_NAME]);

        $this->sessionProcessor->expects(self::once())->method('process')->with($paymentRequest);
        $this->paymentIntentProcessor->expects(self::never())->method('process');

        $this->processor->process($paymentRequest);
    }

    public function test_it_delegates_payment_intent_details_to_payment_intent_processor(): void
    {
        $paymentRequest = $this->createPaymentRequestWithDetails(['object' => PaymentIntent::OBJECT_NAME]);

        $this->sessionProcessor->expects(self::never())->method('process');
        $this->paymentIntentProcessor->expects(self::once())->method('process')->with($paymentRequest);

        $this->processor->process($paymentRequest);
    }

    public function test_it_does_nothing_when_details_object_is_unknown(): void
    {
        $paymentRequest = $this->createPaymentRequestWithDetails(['object' => 'refund']);

        $this->sessionProcessor->expects(self::never())->method('process');
        $this->paymentIntentProcessor->expects(self::never())->method('process');

        $this->processor->process($paymentRequest);
    }

    public function test_it_does_nothing_when_details_is_empty(): void
    {
        $paymentRequest = $this->createPaymentRequestWithDetails([]);

        $this->sessionProcessor->expects(self::never())->method('process');
        $this->paymentIntentProcessor->expects(self::never())->method('process');

        $this->processor->process($paymentRequest);
    }

    /**
     * @param array<string, mixed> $details
     */
    private function createPaymentRequestWithDetails(array $details): PaymentRequestInterface
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getDetails')->willReturn($details);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        return $paymentRequest;
    }
}
