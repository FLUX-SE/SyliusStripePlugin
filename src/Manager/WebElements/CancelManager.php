<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\WebElements;

use FluxSE\SyliusStripePlugin\Manager\StripeClientAwareManagerTrait;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class CancelManager implements CancelManagerInterface
{
    use PaymentIntentServiceAwareTrait;

    /** @use StripeClientAwareManagerTrait<PaymentIntentService> */
    use StripeClientAwareManagerTrait;

    public function __construct(
        private ClientFactoryInterface $stripeClientFactory,
        private ?ParamsProviderInterface $paramsProvider = null,
        private ?OptsProviderInterface $optsProvider = null,
    ) {
    }

    public function cancel(PaymentRequestInterface $paymentRequest, string $id): PaymentIntent
    {
        $stripeClient = $this->getStripeClient($paymentRequest);

        $params = $this->paramsProvider?->getParams($paymentRequest);
        $opts = $this->optsProvider?->getOpts($paymentRequest);

        return $this->getService($stripeClient)->cancel($id, $params, $opts);
    }
}
