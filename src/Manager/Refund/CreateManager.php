<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Refund;

use FluxSE\SyliusStripePlugin\Manager\CreateManagerTrait;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\Service\RefundService;

final class CreateManager implements CreateManagerInterface
{
    use RefundServiceAwareTrait;

    /** @use CreateManagerTrait<RefundService> */
    use CreateManagerTrait;

    public function __construct(
        ClientFactoryInterface $stripeClientFactory,
        ParamsProviderInterface $paramsProvider,
        ?OptsProviderInterface $optsProvider = null,
    ) {
        $this->stripeClientFactory = $stripeClientFactory;
        $this->paramsProvider = $paramsProvider;
        $this->optsProvider = $optsProvider;
    }
}
