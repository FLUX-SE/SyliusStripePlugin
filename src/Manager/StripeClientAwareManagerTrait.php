<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager;

use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\Service\AbstractService;
use Stripe\StripeClient;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as AbstractService
 */
trait StripeClientAwareManagerTrait
{
    private readonly ClientFactoryInterface $stripeClientFactory;

    /**
     * @return T
     */
    abstract private function getService(StripeClient $stripeClient): AbstractService;

    private function getStripeClient(PaymentRequestInterface $paymentRequest): StripeClient
    {
        return $this->stripeClientFactory->createFromPaymentMethod($paymentRequest->getMethod());
    }
}
