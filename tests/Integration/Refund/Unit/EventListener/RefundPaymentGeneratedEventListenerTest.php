<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Integration\Refund\Unit\EventListener;

use FluxSE\SyliusStripePlugin\SyliusRefundPlugin\EventListener\RefundPaymentGeneratedEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\PaymentBundle\Announcer\PaymentRequestAnnouncerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Payment\Factory\PaymentRequestFactoryInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Sylius\RefundPlugin\Event\RefundPaymentGenerated;

final class RefundPaymentGeneratedEventListenerTest extends TestCase
{
    /** @var PaymentRepositoryInterface<PaymentInterface>&MockObject */
    private PaymentRepositoryInterface&MockObject $paymentRepository;

    /** @var PaymentMethodRepositoryInterface<PaymentMethodInterface>&MockObject */
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;

    /** @var PaymentRequestFactoryInterface<PaymentRequestInterface>&MockObject */
    private PaymentRequestFactoryInterface&MockObject $paymentRequestFactory;

    /** @var PaymentRequestRepositoryInterface<PaymentRequestInterface>&MockObject */
    private PaymentRequestRepositoryInterface&MockObject $paymentRequestRepository;

    private PaymentRequestAnnouncerInterface&MockObject $paymentRequestAnnouncer;

    private RefundPaymentGeneratedEventListener $listener;

    protected function setUp(): void
    {
        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->paymentRequestFactory = $this->createMock(PaymentRequestFactoryInterface::class);
        $this->paymentRequestRepository = $this->createMock(PaymentRequestRepositoryInterface::class);
        $this->paymentRequestAnnouncer = $this->createMock(PaymentRequestAnnouncerInterface::class);

        $this->listener = new RefundPaymentGeneratedEventListener(
            $this->paymentRepository,
            $this->paymentMethodRepository,
            $this->paymentRequestFactory,
            $this->paymentRequestRepository,
            $this->paymentRequestAnnouncer,
            ['stripe_checkout', 'stripe_web_elements'],
        );
    }

    public function test_it_does_nothing_when_payment_method_is_not_found(): void
    {
        $event = new RefundPaymentGenerated(1, 'ORDER-001', 1000, 'EUR', 999, 1);

        $this->paymentMethodRepository
            ->expects(self::once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->paymentRequestFactory->expects(self::never())->method('create');
        $this->paymentRequestRepository->expects(self::never())->method('add');
        $this->paymentRequestAnnouncer->expects(self::never())->method('dispatchPaymentRequestCommand');

        ($this->listener)($event);
    }

    public function test_it_does_nothing_when_payment_method_has_no_gateway_config(): void
    {
        $event = new RefundPaymentGenerated(1, 'ORDER-001', 1000, 'EUR', 1, 1);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn(null);

        $this->paymentMethodRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($paymentMethod);

        $this->paymentRequestFactory->expects(self::never())->method('create');

        ($this->listener)($event);
    }

    public function test_it_does_nothing_when_payment_method_is_not_stripe(): void
    {
        $event = new RefundPaymentGenerated(1, 'ORDER-001', 1000, 'EUR', 1, 1);

        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getFactoryName')->willReturn('paypal');

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $this->paymentMethodRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($paymentMethod);

        $this->paymentRequestFactory->expects(self::never())->method('create');

        ($this->listener)($event);
    }

    public function test_it_does_nothing_when_payment_is_not_found(): void
    {
        $event = new RefundPaymentGenerated(1, 'ORDER-001', 1000, 'EUR', 1, 999);

        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getFactoryName')->willReturn('stripe_checkout');

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $this->paymentMethodRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($paymentMethod);

        $this->paymentRepository
            ->expects(self::once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->paymentRequestFactory->expects(self::never())->method('create');

        ($this->listener)($event);
    }

    public function test_it_creates_refund_payment_request_for_stripe_payment(): void
    {
        $event = new RefundPaymentGenerated(1, 'ORDER-001', 1000, 'EUR', 1, 1);

        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);
        $gatewayConfig->method('getFactoryName')->willReturn('stripe_checkout');

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);

        $payment = $this->createMock(PaymentInterface::class);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);

        $this->paymentMethodRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($paymentMethod);

        $this->paymentRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($payment);

        $this->paymentRequestFactory
            ->expects(self::once())
            ->method('create')
            ->with($payment, $paymentMethod)
            ->willReturn($paymentRequest);

        $paymentRequest
            ->expects(self::once())
            ->method('setAction')
            ->with(PaymentRequestInterface::ACTION_REFUND);

        $paymentRequest
            ->expects(self::once())
            ->method('setPayload')
            ->with(['amount' => 1000]);

        $this->paymentRequestRepository
            ->expects(self::once())
            ->method('add')
            ->with($paymentRequest);

        $this->paymentRequestAnnouncer
            ->expects(self::once())
            ->method('dispatchPaymentRequestCommand')
            ->with($paymentRequest);

        ($this->listener)($event);
    }
}

