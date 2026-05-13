<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Dto\ExpressCheckoutConfirmation;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\CartUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ChannelUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\InvalidPayloadException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\PaymentIntentNotCreatedException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\PaymentMethodUnavailableException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Exception\ShippingMethodNotFoundException;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayload;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payload\ExpressCheckoutPayloadReaderInterface;
use FluxSE\SyliusStripePlugin\ExpressCheckout\Payment\CapturePaymentRequestDispatcherInterface;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\CoreBundle\Resolver\CustomerResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class OrderCompleter implements OrderCompleterInterface
{
    /** @param ShippingMethodRepositoryInterface<ShippingMethodInterface> $shippingMethodRepository */
    public function __construct(
        private CartContextInterface $cartContext,
        private ChannelContextInterface $channelContext,
        private ExpressCheckoutPaymentMethodResolverInterface $paymentMethodResolver,
        private ExpressCheckoutAddressNormalizerInterface $addressNormalizer,
        private ExpressCheckoutPayloadReaderInterface $payloadReader,
        private CustomerResolverInterface $customerResolver,
        private StateMachineInterface $stateMachine,
        private FactoryInterface $paymentFactory,
        private ShippingMethodRepositoryInterface $shippingMethodRepository,
        private CapturePaymentRequestDispatcherInterface $capturePaymentRequestDispatcher,
        private AfterUrlProviderInterface $afterUrlProvider,
    ) {
    }

    public function complete(Request $request): ExpressCheckoutConfirmation
    {
        $this->resolveChannel();
        $cart = $this->resolveCart();
        $paymentMethod = $this->resolvePaymentMethod($cart);

        $payload = $this->payloadReader->read($request);

        $email = $payload->getEmail();
        if (null === $email) {
            throw InvalidPayloadException::missingEmail();
        }

        $shippingAddress = $this->normalizeShipping($payload);
        // ECE wallets sometimes return placeholder billing data (e.g. Google Pay sandbox
        // populates billingDetails with "Card Holder Name" + Googleplex address). Since
        // the wallet popup never lets the customer pick a separate billing address, the
        // shipping address is the only intent the customer expressed — clone it.
        $billingAddress = clone $shippingAddress;

        $shippingMethod = $this->resolveShippingMethod($payload);

        $cart->setCustomer($this->customerResolver->resolve($email));
        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_ADDRESS);

        $shipment = $cart->getShipments()->first();
        if (!$shipment instanceof ShipmentInterface) {
            throw ShippingMethodNotFoundException::shipmentMissing();
        }

        $shipment->setMethod($shippingMethod);
        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_SELECT_SHIPPING);

        $payment = $this->createPayment($cart, $paymentMethod);
        $cart->addPayment($payment);
        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_SELECT_PAYMENT);

        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_COMPLETE);

        $paymentRequest = $this->capturePaymentRequestDispatcher->dispatch($payment, $paymentMethod);

        $clientSecret = $this->extractClientSecret($paymentRequest);
        if (null === $clientSecret) {
            throw PaymentIntentNotCreatedException::missingClientSecret();
        }

        return new ExpressCheckoutConfirmation(
            clientSecret: $clientSecret,
            returnUrl: $this->afterUrlProvider->getUrl($paymentRequest, AfterUrlProviderInterface::ACTION_URL),
        );
    }

    private function resolveChannel(): ChannelInterface
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            throw ChannelUnavailableException::notFound();
        }

        if (!$channel instanceof ChannelInterface) {
            throw ChannelUnavailableException::notFound();
        }

        return $channel;
    }

    private function resolveCart(): OrderInterface
    {
        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            throw CartUnavailableException::notFound();
        }

        if (!$cart instanceof OrderInterface) {
            throw CartUnavailableException::notFound();
        }

        if ($cart->getItems()->isEmpty()) {
            throw CartUnavailableException::empty();
        }

        return $cart;
    }

    private function resolvePaymentMethod(OrderInterface $cart): PaymentMethodInterface
    {
        $channel = $cart->getChannel();
        if (!$channel instanceof ChannelInterface) {
            throw ChannelUnavailableException::notFound();
        }

        $paymentMethod = $this->paymentMethodResolver->resolveForChannel($channel);
        if (null === $paymentMethod) {
            throw PaymentMethodUnavailableException::notConfigured();
        }

        return $paymentMethod;
    }

    private function normalizeShipping(ExpressCheckoutPayload $payload): AddressInterface
    {
        try {
            return $this->addressNormalizer->normalizeShipping($payload->raw());
        } catch (\LogicException | \InvalidArgumentException $exception) {
            throw InvalidPayloadException::invalidShippingAddress($exception->getMessage());
        }
    }

    private function resolveShippingMethod(ExpressCheckoutPayload $payload): ShippingMethodInterface
    {
        $shippingRateId = $payload->getShippingRateId();
        if (null === $shippingRateId) {
            throw InvalidPayloadException::missingShippingRateId();
        }

        /** @var ShippingMethodInterface|null $shippingMethod */
        $shippingMethod = $this->shippingMethodRepository->findOneBy(['code' => $shippingRateId]);
        if (null === $shippingMethod) {
            throw ShippingMethodNotFoundException::forCode($shippingRateId);
        }

        return $shippingMethod;
    }

    private function applyTransition(OrderInterface $cart, string $transition): void
    {
        $this->stateMachine->apply($cart, OrderCheckoutTransitions::GRAPH, $transition);
    }

    private function createPayment(OrderInterface $cart, PaymentMethodInterface $paymentMethod): PaymentInterface
    {
        $currencyCode = $cart->getCurrencyCode();
        if (null === $currencyCode) {
            throw PaymentIntentNotCreatedException::cartHasNoCurrency();
        }

        /** @var PaymentInterface $payment */
        $payment = $this->paymentFactory->createNew();
        $payment->setMethod($paymentMethod);
        $payment->setCurrencyCode($currencyCode);
        $payment->setAmount($cart->getTotal());

        return $payment;
    }

    private function extractClientSecret(PaymentRequestInterface $paymentRequest): ?string
    {
        $secret = $paymentRequest->getResponseData()['client_secret'] ?? null;

        return is_string($secret) && '' !== $secret ? $secret : null;
    }
}
