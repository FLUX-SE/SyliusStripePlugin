<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Checkout;

use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerTrait;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\Checkout\Session;
use Stripe\Service\Checkout\SessionService;

final class RetrieveManager implements RetrieveManagerInterface
{
    use CheckoutSessionServiceAwareTrait;

    /** @use RetrieveManagerTrait<SessionService, Session> */
    use RetrieveManagerTrait;

    /**
     * @param ParamsProviderInterface<Session>|null $paramsProvider
     */
    public function __construct(
        ClientFactoryInterface $stripeClientFactory,
        ?ParamsProviderInterface $paramsProvider = null,
        ?OptsProviderInterface $optsProvider = null,
    ) {
        $this->stripeClientFactory = $stripeClientFactory;
        $this->paramsProvider = $paramsProvider;
        $this->optsProvider = $optsProvider;
    }
}
