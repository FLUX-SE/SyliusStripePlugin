<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\ApiResource;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as ApiResource
 * @implements InnerParamsProviderInterface<T>
 */
final readonly class FirstOrderFlagMetadataProvider implements InnerParamsProviderInterface
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $order = $payment->getOrder();
        if (null === $order) {
            return;
        }

        /** @var CustomerInterface|null $customer */
        $customer = $order->getCustomer();
        if (null === $customer) {
            return;
        }

        $params['first_order'] = 1 === $this->orderRepository->countByCustomer($customer) ? 'yes' : 'no';
    }
}
