<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Controller\Shop\ExpressCheckout;

use Doctrine\ORM\EntityManagerInterface;
use FluxSE\SyliusStripePlugin\Command\WebElements\CapturePaymentRequest;
use FluxSE\SyliusStripePlugin\Normalizer\ExpressCheckoutAddressNormalizerInterface;
use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FluxSE\SyliusStripePlugin\Resolver\ExpressCheckoutPaymentMethodResolverInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\CoreBundle\Resolver\CustomerResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Payment\Factory\PaymentRequestFactoryInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ConfirmAction
{
    private const GRAPH_ORDER_CHECKOUT = 'sylius_order_checkout';

    private const TRANSITION_ADDRESS = 'address';

    private const TRANSITION_SELECT_SHIPPING = 'select_shipping';

    private const TRANSITION_SELECT_PAYMENT = 'select_payment';

    private const TRANSITION_COMPLETE = 'complete';

    /**
     * @param PaymentRequestFactoryInterface<PaymentRequestInterface> $paymentRequestFactory
     * @param ShippingMethodRepositoryInterface<ShippingMethodInterface> $shippingMethodRepository
     */
    public function __construct(
        private CartContextInterface $cartContext,
        private ChannelContextInterface $channelContext,
        private ExpressCheckoutPaymentMethodResolverInterface $paymentMethodResolver,
        private ExpressCheckoutAddressNormalizerInterface $addressNormalizer,
        private CustomerResolverInterface $customerResolver,
        private StateMachineInterface $stateMachine,
        private FactoryInterface $paymentFactory,
        private PaymentRequestFactoryInterface $paymentRequestFactory,
        private MessageBusInterface $paymentRequestCommandBus,
        private ShippingMethodRepositoryInterface $shippingMethodRepository,
        private AfterUrlProviderInterface $afterUrlProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return $this->error('Channel not found.');
        }

        if (!$channel instanceof ChannelInterface) {
            return $this->error('Channel not found.');
        }

        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            return $this->error('Cart not found.');
        }

        if (!$cart instanceof OrderInterface || $cart->getItems()->isEmpty()) {
            return $this->error('Cart is empty.');
        }

        $paymentMethod = $this->paymentMethodResolver->resolveForChannel($channel);
        if (null === $paymentMethod) {
            return $this->error('No Express Checkout payment method is configured for this channel.');
        }

        $payload = $this->decodePayload($request);

        $email = $this->extractEmail($payload);
        if (null === $email) {
            return $this->error('Missing customer email in payload.');
        }

        try {
            $shippingAddress = $this->addressNormalizer->normalizeShipping($payload);
        } catch (\LogicException|\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage());
        }

        $billingAddress = $this->addressNormalizer->normalizeBilling($payload, $shippingAddress);

        $shippingRateId = $this->extractShippingRateId($payload);
        if (null === $shippingRateId) {
            return $this->error('Missing shipping rate id in payload.');
        }

        /** @var ShippingMethodInterface|null $shippingMethod */
        $shippingMethod = $this->shippingMethodRepository->findOneBy(['code' => $shippingRateId]);
        if (null === $shippingMethod) {
            return $this->error(sprintf('Unknown shipping rate "%s".', $shippingRateId));
        }

        $cart->setCustomer($this->customerResolver->resolve($email));
        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $this->stateMachine->apply($cart, self::GRAPH_ORDER_CHECKOUT, self::TRANSITION_ADDRESS);

        $shipment = $cart->getShipments()->first();
        if (!$shipment instanceof ShipmentInterface) {
            return $this->error('Cart has no shipment to attach a shipping method to.');
        }

        $shipment->setMethod($shippingMethod);
        $this->stateMachine->apply($cart, self::GRAPH_ORDER_CHECKOUT, self::TRANSITION_SELECT_SHIPPING);

        $payment = $this->createPayment($cart, $paymentMethod);
        $cart->addPayment($payment);
        $this->stateMachine->apply($cart, self::GRAPH_ORDER_CHECKOUT, self::TRANSITION_SELECT_PAYMENT);

        $this->stateMachine->apply($cart, self::GRAPH_ORDER_CHECKOUT, self::TRANSITION_COMPLETE);

        $paymentRequest = $this->createCapturePaymentRequest($payment, $paymentMethod);
        $this->entityManager->persist($paymentRequest);

        // Always force the Web Elements capture command — Stripe Express Checkout Element
        // only works with the PaymentIntent stack, even when the resolved PaymentMethod is
        // configured as stripe_checkout (the publishable/secret keys are per-account).
        $this->paymentRequestCommandBus->dispatch(new CapturePaymentRequest($paymentRequest->getId()));
        $this->entityManager->flush();

        $clientSecret = $this->extractClientSecret($paymentRequest);
        if (null === $clientSecret) {
            return $this->error('Stripe did not return a client_secret for the PaymentIntent.');
        }

        return new JsonResponse([
            'clientSecret' => $clientSecret,
            'returnUrl' => $this->afterUrlProvider->getUrl($paymentRequest, AfterUrlProviderInterface::ACTION_URL),
        ]);
    }

    private function createPayment(OrderInterface $cart, PaymentMethodInterface $paymentMethod): PaymentInterface
    {
        $currencyCode = $cart->getCurrencyCode();
        if (null === $currencyCode) {
            throw new \LogicException('Cart must have a currency code set before creating an Express Checkout payment.');
        }

        /** @var PaymentInterface $payment */
        $payment = $this->paymentFactory->createNew();
        $payment->setMethod($paymentMethod);
        $payment->setCurrencyCode($currencyCode);
        $payment->setAmount($cart->getTotal());

        return $payment;
    }

    private function createCapturePaymentRequest(PaymentInterface $payment, PaymentMethodInterface $paymentMethod): PaymentRequestInterface
    {
        // Sylius's PaymentRequestFactory rejects FactoryInterface::createNew() and instead
        // exposes create($payment, $method) which wires both associations in the constructor.
        $paymentRequest = $this->paymentRequestFactory->create($payment, $paymentMethod);
        $paymentRequest->setAction(PaymentRequestInterface::ACTION_CAPTURE);

        return $paymentRequest;
    }

    /** @param array<string, mixed> $payload */
    private function extractEmail(array $payload): ?string
    {
        $billingDetails = $payload['billingDetails'] ?? null;
        if (!is_array($billingDetails)) {
            return null;
        }

        $email = $billingDetails['email'] ?? null;

        return is_string($email) && '' !== $email ? $email : null;
    }

    /** @param array<string, mixed> $payload */
    private function extractShippingRateId(array $payload): ?string
    {
        $shippingRate = $payload['shippingRate'] ?? null;
        if (!is_array($shippingRate)) {
            return null;
        }

        $id = $shippingRate['id'] ?? null;

        return is_string($id) && '' !== $id ? $id : null;
    }

    private function extractClientSecret(PaymentRequestInterface $paymentRequest): ?string
    {
        $data = $paymentRequest->getResponseData();
        $secret = $data['client_secret'] ?? null;

        return is_string($secret) && '' !== $secret ? $secret : null;
    }

    /** @return array<string, mixed> */
    private function decodePayload(Request $request): array
    {
        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function error(string $message, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
