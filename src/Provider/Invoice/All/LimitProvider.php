<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Invoice\All;

use FluxSE\SyliusStripePlugin\Provider\InnerParamsProviderInterface;
use Stripe\Checkout\Session;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class LimitProvider implements InnerParamsProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        /** @var string|null $object */
        $object = $paymentRequest->getPayment()->getDetails()['object'] ?? null;
        if (Session::OBJECT_NAME !== $object) {
            return;
        }

        /** @var string|null $mode */
        $mode = $paymentRequest->getPayment()->getDetails()['mode'] ?? null;
        if (Session::MODE_SUBSCRIPTION !== $mode) {
            return;
        }

        $params['limit'] = 1;
    }
}
