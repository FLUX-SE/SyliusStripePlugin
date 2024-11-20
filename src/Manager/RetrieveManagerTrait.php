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
 * @template O as ApiResource
 */
trait RetrieveManagerTrait
{
    /** @use StripeClientAwareManagerTrait<T> */
    use StripeClientAwareManagerTrait;

    /**
     * @var ParamsProviderInterface<O>|null $paramsProvider
     */
    private ?ParamsProviderInterface $paramsProvider = null;

    private ?OptsProviderInterface $optsProvider = null;

    public function retrieve(PaymentRequestInterface $paymentRequest, string $id): ApiResource
    {
        $stripeClient = $this->getStripeClient($paymentRequest);

        $params = $this->paramsProvider?->getParams($paymentRequest);
        $opts = $this->optsProvider?->getOpts($paymentRequest);

        return $this->getService($stripeClient)->retrieve($id, $params, $opts);
    }
}
