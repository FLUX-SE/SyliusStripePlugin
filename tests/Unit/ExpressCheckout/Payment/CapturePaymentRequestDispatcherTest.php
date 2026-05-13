<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\ExpressCheckout\Payment;

use Doctrine\ORM\EntityManagerInterface;
use FluxSE\SyliusStripePlugin\Command\WebElements\CapturePaymentRequest;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payment\CapturePaymentRequestDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Factory\PaymentRequestFactoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CapturePaymentRequestDispatcherTest extends TestCase
{
    /** @var PaymentRequestFactoryInterface<PaymentRequestInterface>&MockObject */
    private PaymentRequestFactoryInterface $paymentRequestFactory;

    /** @var MessageBusInterface&MockObject */
    private MessageBusInterface $commandBus;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private CapturePaymentRequestDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->paymentRequestFactory = $this->createMock(PaymentRequestFactoryInterface::class);
        $this->commandBus = $this->createMock(MessageBusInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->dispatcher = new CapturePaymentRequestDispatcher(
            $this->paymentRequestFactory,
            $this->commandBus,
            $this->entityManager,
        );
    }

    public function test_it_creates_persists_dispatches_and_flushes(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getId')->willReturn('payment-request-id');
        $paymentRequest->expects(self::once())
            ->method('setAction')
            ->with(PaymentRequestInterface::ACTION_CAPTURE);

        $this->paymentRequestFactory->expects(self::once())
            ->method('create')
            ->with($payment, $paymentMethod)
            ->willReturn($paymentRequest);

        $callOrder = [];
        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with($paymentRequest)
            ->willReturnCallback(function () use (&$callOrder): void {
                $callOrder[] = 'persist';
            });

        $this->commandBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$callOrder): Envelope {
                $callOrder[] = 'dispatch';
                self::assertInstanceOf(CapturePaymentRequest::class, $message);
                self::assertSame('payment-request-id', $message->getHash());

                return new Envelope($message);
            });

        $this->entityManager->expects(self::once())
            ->method('flush')
            ->willReturnCallback(function () use (&$callOrder): void {
                $callOrder[] = 'flush';
            });

        $result = $this->dispatcher->dispatch($payment, $paymentMethod);

        self::assertSame($paymentRequest, $result);
        self::assertSame(['persist', 'dispatch', 'flush'], $callOrder);
    }
}
