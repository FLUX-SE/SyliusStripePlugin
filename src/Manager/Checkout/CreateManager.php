<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Checkout;

use FluxSE\SyliusStripePlugin\Manager\CreateManagerTrait;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\Checkout\Session;
use Stripe\Service\Checkout\SessionService;

final class CreateManager implements CreateManagerInterface
{
    use CheckoutSessionServiceAwareTrait;

    /** @use CreateManagerTrait<SessionService, Session> */
    use CreateManagerTrait;

    /**
     * @param ParamsProviderInterface<Session> $paramsProvider
     */
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
