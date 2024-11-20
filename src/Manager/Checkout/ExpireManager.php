<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Manager\Checkout;

use FluxSE\SyliusStripePlugin\Manager\StripeClientAwareManagerTrait;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\Checkout\Session;
use Stripe\Service\Checkout\SessionService;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class ExpireManager implements ExpireManagerInterface
{
    use CheckoutSessionServiceAwareTrait;

    /** @use StripeClientAwareManagerTrait<SessionService> */
    use StripeClientAwareManagerTrait;

    /**
     * @param ParamsProviderInterface<Session>|null $paramsProvider
     */
    public function __construct(
        ClientFactoryInterface $stripeClientFactory,
        private ?ParamsProviderInterface $paramsProvider = null,
        private ?OptsProviderInterface $optsProvider = null,
    ) {
        $this->stripeClientFactory = $stripeClientFactory;
    }

    public function expire(PaymentRequestInterface $paymentRequest, string $id): Session
    {
        $stripeClient = $this->getStripeClient($paymentRequest);

        $params = $this->paramsProvider?->getParams($paymentRequest);
        $opts = $this->optsProvider?->getOpts($paymentRequest);

        return $this->getService($stripeClient)->expire($id, $params, $opts);
    }
}
