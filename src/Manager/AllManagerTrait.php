<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager;

use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use Stripe\ApiResource;
use Stripe\Collection;
use Stripe\Service\AbstractService;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @template T as AbstractService
 * @template O as ApiResource
 */
trait AllManagerTrait
{
    /** @use StripeClientAwareManagerTrait<T> */
    use StripeClientAwareManagerTrait;

    /** @var ParamsProviderInterface<O> */
    private readonly ParamsProviderInterface $paramsProvider;

    private ?OptsProviderInterface $optsProvider = null;

    public function all(PaymentRequestInterface $paymentRequest): Collection
    {
        $stripeClient = $this->getStripeClient($paymentRequest);

        $params = $this->paramsProvider->getParams($paymentRequest);
        $opts = $this->optsProvider?->getOpts($paymentRequest);

        return $this->getService($stripeClient)->all($params, $opts);
    }
}
