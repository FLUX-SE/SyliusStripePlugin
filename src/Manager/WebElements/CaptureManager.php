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

final class CaptureManager implements CaptureManagerInterface
{
    use PaymentIntentServiceAwareTrait;

    /** @use StripeClientAwareManagerTrait<PaymentIntentService> */
    use StripeClientAwareManagerTrait;

    /**
     * @param ParamsProviderInterface<PaymentIntent>|null $paramsProvider
     */
    public function __construct(
        ClientFactoryInterface $stripeClientFactory,
        private ?ParamsProviderInterface $paramsProvider = null,
        private ?OptsProviderInterface $optsProvider = null,
    ) {
        $this->stripeClientFactory = $stripeClientFactory;
    }

    public function capture(PaymentRequestInterface $paymentRequest, string $id): PaymentIntent
    {
        $stripeClient = $this->getStripeClient($paymentRequest);

        $params = $this->paramsProvider?->getParams($paymentRequest);
        $opts = $this->optsProvider?->getOpts($paymentRequest);

        return $this->getService($stripeClient)->capture($id, $params, $opts);
    }
}
