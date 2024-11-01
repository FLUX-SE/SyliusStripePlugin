<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager;

use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use Stripe\ApiResource;
use Stripe\Service\AbstractService;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as AbstractService
 */
trait CreateManagerTrait
{
    /** @use StripeClientAwareManagerTrait<T> */
    use StripeClientAwareManagerTrait;

    private readonly ParamsProviderInterface $paramsProvider;

    private ?OptsProviderInterface $optsProvider = null;

    public function create(PaymentRequestInterface $paymentRequest): ApiResource
    {
        $stripeClient = $this->getStripeClient($paymentRequest);

        $params = $this->paramsProvider->getParams($paymentRequest);
        $opts = $this->optsProvider?->getOpts($paymentRequest);

        return $this->getService($stripeClient)->create($params, $opts);
    }
}
