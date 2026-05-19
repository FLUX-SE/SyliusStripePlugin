<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\CommandProvider\Checkout;

use FluxSE\SyliusStripePlugin\CommandProvider\Checkout\CheckoutOrPaymentIntentCommandProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class CheckoutOrPaymentIntentCommandProviderTest extends TestCase
{
    /** @var PaymentRequestCommandProviderInterface&MockObject */
    private PaymentRequestCommandProviderInterface $checkoutProvider;

    /** @var PaymentRequestCommandProviderInterface&MockObject */
    private PaymentRequestCommandProviderInterface $webElementsProvider;

    private CheckoutOrPaymentIntentCommandProvider $commandProvider;

    protected function setUp(): void
    {
        $this->checkoutProvider = $this->createMock(PaymentRequestCommandProviderInterface::class);
        $this->webElementsProvider = $this->createMock(PaymentRequestCommandProviderInterface::class);

        $this->commandProvider = new CheckoutOrPaymentIntentCommandProvider(
            $this->checkoutProvider,
            $this->webElementsProvider,
        );
    }

    public function test_it_delegates_payment_intent_details_to_web_elements_provider(): void
    {
        $paymentRequest = $this->createPaymentRequestWithDetails(['object' => PaymentIntent::OBJECT_NAME]);
        $command = new \stdClass();

        $this->webElementsProvider->expects(self::once())->method('provide')->with($paymentRequest)->willReturn($command);
        $this->checkoutProvider->expects(self::never())->method('provide');

        self::assertSame($command, $this->commandProvider->provide($paymentRequest));
    }

    public function test_it_delegates_checkout_session_details_to_checkout_provider(): void
    {
        $paymentRequest = $this->createPaymentRequestWithDetails(['object' => Session::OBJECT_NAME]);
        $command = new \stdClass();

        $this->checkoutProvider->expects(self::once())->method('provide')->with($paymentRequest)->willReturn($command);
        $this->webElementsProvider->expects(self::never())->method('provide');

        self::assertSame($command, $this->commandProvider->provide($paymentRequest));
    }

    public function test_it_falls_back_to_checkout_provider_when_details_is_empty(): void
    {
        $paymentRequest = $this->createPaymentRequestWithDetails([]);
        $command = new \stdClass();

        $this->checkoutProvider->expects(self::once())->method('provide')->with($paymentRequest)->willReturn($command);
        $this->webElementsProvider->expects(self::never())->method('provide');

        self::assertSame($command, $this->commandProvider->provide($paymentRequest));
    }

    public function test_supports_follows_the_picked_provider(): void
    {
        $paymentRequest = $this->createPaymentRequestWithDetails(['object' => PaymentIntent::OBJECT_NAME]);

        $this->webElementsProvider->expects(self::once())->method('supports')->with($paymentRequest)->willReturn(true);
        $this->checkoutProvider->expects(self::never())->method('supports');

        self::assertTrue($this->commandProvider->supports($paymentRequest));
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
