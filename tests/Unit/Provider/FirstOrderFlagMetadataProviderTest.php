<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider;

use FluxSE\SyliusStripePlugin\Provider\FirstOrderFlagMetadataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class FirstOrderFlagMetadataProviderTest extends TestCase
{
    public function test_it_marks_first_order_when_customer_has_one_order(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $orderRepository->method('countByCustomer')->with($customer)->willReturn(1);

        $provider = new FirstOrderFlagMetadataProvider($orderRepository);

        $params = [];
        $provider->provide($this->wrap($customer), $params);

        self::assertSame(['first_order' => 'yes'], $params);
    }

    public function test_it_marks_returning_customer_when_count_is_greater_than_one(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $orderRepository->method('countByCustomer')->with($customer)->willReturn(7);

        $provider = new FirstOrderFlagMetadataProvider($orderRepository);

        $params = [];
        $provider->provide($this->wrap($customer), $params);

        self::assertSame(['first_order' => 'no'], $params);
    }

    public function test_it_omits_key_for_guest_checkout(): void
    {
        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $orderRepository->expects(self::never())->method('countByCustomer');

        $provider = new FirstOrderFlagMetadataProvider($orderRepository);

        $params = [];
        $provider->provide($this->wrap(null), $params);

        self::assertSame([], $params);
    }

    public function test_it_returns_early_when_order_is_null(): void
    {
        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $orderRepository->expects(self::never())->method('countByCustomer');

        $provider = new FirstOrderFlagMetadataProvider($orderRepository);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn(null);
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        $params = ['untouched' => true];
        $provider->provide($paymentRequest, $params);

        self::assertSame(['untouched' => true], $params);
    }

    private function wrap(?CustomerInterface $customer): PaymentRequestInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn($customer);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        return $paymentRequest;
    }
}
