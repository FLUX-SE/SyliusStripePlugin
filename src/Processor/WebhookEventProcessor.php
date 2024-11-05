<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Processor;

use FluxSE\SyliusStripePlugin\Manager\RetrieveManagerInterface;
use Stripe\ApiResource;
use Stripe\Event;
use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

final readonly class WebhookEventProcessor implements WebhookEventProcessorInterface
{
    /**
     * @param array<string, string[]> $supportedFactoriesAndEvents
     * @param RetrieveManagerInterface<ApiResource> $retrieveManager
     */
    public function __construct(
        private array $supportedFactoriesAndEvents,
        private RetrieveManagerInterface $retrieveManager,
    ) {
    }

    public function process(PaymentRequestInterface $paymentRequest, Event $event): void
    {
        /** @var StripeObject|null $object */
        $object = $event->data->object;
        Assert::isInstanceOf(
            $object,
            StripeObject::class,
            'The Stripe event data object must be an instance of StripeObject.',
        );

        $id = $object->id;
        Assert::notNull($id, 'The Stripe event data object "id" must not be null.');

        $stripeApiResource = $this->retrieveManager->retrieve($paymentRequest, $id);

        $paymentRequest->getPayment()->setDetails($stripeApiResource->toArray());
    }

    public function supports(PaymentRequestInterface $paymentRequest, Event $event): bool
    {
        $factoryName = $paymentRequest->getMethod()->getGatewayConfig()?->getFactoryName() ?? '';
        if (false === array_key_exists($factoryName, $this->supportedFactoriesAndEvents)) {
            return false;
        }

        return in_array($event->type, $this->supportedFactoriesAndEvents[$factoryName], true);
    }
}
