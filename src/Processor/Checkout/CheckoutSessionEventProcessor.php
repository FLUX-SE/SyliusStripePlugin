<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor\Checkout;

use FluxSE\SyliusStripePlugin\Processor\WebhookEventProcessorInterface;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class CheckoutSessionEventProcessor implements WebhookEventProcessorInterface
{
    /**
     * @param string[] $supportedEventTypes
     */
    public function __construct(
        private array $supportedEventTypes,
        private ClientFactoryInterface $stripeClientFactory,
        private ParamsProviderInterface $checkoutSessionParamsProvider,
        private OptsProviderInterface $checkoutSessionOptsProvider,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest, Event $event): void {
        /** @var StripeObject|null $object */
        $object = $event->data->object;
        if (false === $object instanceof StripeObject) {
            throw new \LogicException('The Stripe event data object must be an instance of StripeObject.');
        }

        /** @var StripeClient $stripe */
        $stripe = $this->stripeClientFactory->createFromPaymentMethod($paymentRequest->getMethod());

        $params = $this->checkoutSessionParamsProvider->getParams($paymentRequest, 'retrieve');
        $opts = $this->checkoutSessionOptsProvider->getOpts($paymentRequest, 'retrieve');
        $session = $stripe->checkout->sessions->retrieve((string) $object->id, $params, $opts);

        $paymentRequest->getPayment()->setDetails($session->toArray());
    }

    public function supports(PaymentRequestInterface $paymentRequest, Event $event): bool
    {
        return in_array($event->type, $this->supportedEventTypes, true);
    }
}
