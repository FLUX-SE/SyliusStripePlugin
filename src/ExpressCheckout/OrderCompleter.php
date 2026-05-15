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
use Sylius\Abstraction\StateMachine\Exception\StateMachineExecutionException;
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
use Symfony\Component\HttpFoundation\RequestStack;

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
        private RequestStack $requestStack,
    ) {
    }

    public function complete(Request $request): ExpressCheckoutConfirmation
    {
        $this->resolveChannel();
        $cart = $this->resolveCart();
        if (OrderInterface::STATE_CART !== $cart->getState()) {
            throw CartUnavailableException::alreadyPlaced();
        }
        $paymentMethod = $this->resolvePaymentMethod($cart);

        $payload = $this->payloadReader->read($request);

        $email = $payload->getEmail();
        if (null === $email) {
            throw InvalidPayloadException::missingEmail();
        }

        if (null === $cart->getCustomer()) {
            $cart->setCustomer($this->customerResolver->resolve($email));
        }

        if ($cart->isShippingRequired()) {
            $this->applyPhysicalCheckout($cart, $payload);
        } else {
            $this->applyDigitalCheckout($cart, $payload);
        }

        // ECE may be triggered after the customer already picked a payment method on
        // /checkout/select-payment, leaving a pending Payment on the cart. Drop those
        // before adding the ECE one so the order does not end up with two payments —
        // Order::addPayment() only dedups by object identity, not state.
        foreach ($cart->getPayments() as $existingPayment) {
            if (PaymentInterface::STATE_NEW === $existingPayment->getState()) {
                $cart->removePayment($existingPayment);
            }
        }

        $payment = $this->createPayment($cart, $paymentMethod);
        $cart->addPayment($payment);
        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_SELECT_PAYMENT);

        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_COMPLETE);

        // Sylius's thank-you action reads the placed order from session attribute
        // `sylius_order_id`; in the regular checkout it is set by OrderPayController::payAction,
        // which the ECE flow bypasses. Set it here so the customer lands on /thank-you
        // after stripe.confirmPayment redirects instead of being sent back to the homepage.
        $this->requestStack->getSession()->set('sylius_order_id', $cart->getId());

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

    private function applyPhysicalCheckout(OrderInterface $cart, ExpressCheckoutPayload $payload): void
    {
        $shippingAddress = $this->normalizeShipping($payload);
        // ECE wallets sometimes return placeholder billing data (e.g. Google Pay sandbox
        // populates billingDetails with "Card Holder Name" + Googleplex address). Since
        // the wallet popup never lets the customer pick a separate billing address, the
        // shipping address is the only intent the customer expressed — clone it.
        $billingAddress = clone $shippingAddress;
        $shippingMethod = $this->resolveShippingMethod($payload);

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);
        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_ADDRESS);

        $shipment = $cart->getShipments()->first();
        if (!$shipment instanceof ShipmentInterface) {
            throw ShippingMethodNotFoundException::shipmentMissing();
        }

        $shipment->setMethod($shippingMethod);
        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_SELECT_SHIPPING);
    }

    private function applyDigitalCheckout(OrderInterface $cart, ExpressCheckoutPayload $payload): void
    {
        // Wallet popup never asks for an address when shippingAddressRequired=false (set by
        // ConfigurationProvider for digital-only carts). billingDetails is the only address
        // Stripe sends back — use it as billing-only and let the state machine auto-skip
        // the shipping step via the sylius_skip_shipping after-callback on TRANSITION_ADDRESS.
        $billingAddress = $this->normalizeBilling($payload);
        $cart->setBillingAddress($billingAddress);
        $this->applyTransition($cart, OrderCheckoutTransitions::TRANSITION_ADDRESS);
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

    private function normalizeBilling(ExpressCheckoutPayload $payload): AddressInterface
    {
        try {
            return $this->addressNormalizer->normalizeBilling($payload->raw());
        } catch (\LogicException | \InvalidArgumentException $exception) {
            throw InvalidPayloadException::invalidBillingAddress($exception->getMessage());
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
        try {
            $this->stateMachine->apply($cart, OrderCheckoutTransitions::GRAPH, $transition);
        } catch (StateMachineExecutionException) {
            // Race-condition safety net for parallel POSTs that bypass the STATE_CART
            // guard in complete(): once the first request flips the cart out of `cart`
            // state, subsequent transitions raise SM exception — surface it as 422.
            throw CartUnavailableException::alreadyPlaced();
        }
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
