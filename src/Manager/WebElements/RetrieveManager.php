<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\WebElements;

use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerTrait;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;

final class RetrieveManager implements RetrieveManagerInterface
{
    use PaymentIntentServiceAwareTrait;

    /** @use RetrieveManagerTrait<PaymentIntentService, PaymentIntent> */
    use RetrieveManagerTrait;

    /**
     * @param ParamsProviderInterface<PaymentIntent>|null $paramsProvider
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
