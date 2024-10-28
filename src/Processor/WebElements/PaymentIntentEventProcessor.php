<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor\WebElements;

use FluxSE\SyliusStripePlugin\Processor\WebhookEventProcessorInterface;
use FluxSE\SyliusStripePlugin\Provider\OptsProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final readonly class PaymentIntentEventProcessor implements WebhookEventProcessorInterface
{
    /**
     * @param string[] $supportedEventTypes
     */
    public function __construct(
        private array $supportedEventTypes,
        private ClientFactoryInterface $stripeClientFactory,
        private ParamsProviderInterface $paymentIntentParamsProvider,
        private OptsProviderInterface $paymentIntentOptsProvider,
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

        $params = $this->paymentIntentParamsProvider->getParams($paymentRequest, 'retrieve');
        $opts = $this->paymentIntentOptsProvider->getOpts($paymentRequest, 'retrieve');
        $paymentIntent = $stripe->paymentIntents->retrieve((string) $object->id, $params, $opts);

        $paymentRequest->getPayment()->setDetails($paymentIntent->toArray());
    }

    public function supports(PaymentRequestInterface $paymentRequest, Event $event): bool
    {
        return in_array($event->type, $this->supportedEventTypes, true);
    }
}
