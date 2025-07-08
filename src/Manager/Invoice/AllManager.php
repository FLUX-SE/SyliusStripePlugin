<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Invoice;

use FluxSE\SyliusStripePlugin\Manager\AllManagerTrait;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\Invoice;
use Stripe\Service\InvoiceService;

final class AllManager implements AllManagerInterface
{
    use InvoiceServiceAwareTrait;

    /** @use AllManagerTrait<InvoiceService, Invoice> */
    use AllManagerTrait;

    /**
     * @param ParamsProviderInterface<Invoice> $paramsProvider
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
