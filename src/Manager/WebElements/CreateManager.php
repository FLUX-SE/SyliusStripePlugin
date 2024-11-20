<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\WebElements;

use FluxSE\SyliusStripePlugin\Manager\CreateManagerTrait;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;

final class CreateManager implements CreateManagerInterface
{
    use PaymentIntentServiceAwareTrait;

    /** @use CreateManagerTrait<PaymentIntentService, PaymentIntent> */
    use CreateManagerTrait;

    /**
     * @param ParamsProviderInterface<PaymentIntent> $paramsProvider
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
