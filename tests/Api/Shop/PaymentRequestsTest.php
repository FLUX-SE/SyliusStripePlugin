<?php

declare(strict_types=1);

namespace Api\Shop;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\FluxSE\SyliusStripePlugin\Api\JsonApiTestCase;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeCheckoutMocker;
use Webmozart\Assert\Assert;

final class PaymentRequestsTest extends JsonApiTestCase
{
    private StripeCheckoutMocker $stripeCheckoutMocker;

    protected function setUp(): void
    {
        $this->setUpShopUserContext();

        $this->stripeCheckoutMocker = static::getContainer()->get(StripeCheckoutMocker::class);

        parent::setUp();
    }

    /**
     * @dataProvider createPaymentRequestProvider
     *
     * @param string[] $fixturesPaths
     *
     * @throws \JsonException
     */
    public function test_it_creates_a_payment_request(string $method, array $fixturesPaths, string $responsePath): void
    {
        $fixtures = $this->loadFixturesFromFiles($fixturesPaths);

        $this->stripeCheckoutMocker->mockCaptureOrAuthorize();
        /** @var OrderInterface $order */
        $order = $fixtures['order'];
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $fixtures[$method];
        /** @var PaymentInterface $payment */
        $payment = $fixtures['payment'];

        $kernelBrowser = $this->client;
        Assert::notNull($kernelBrowser);

        $kernelBrowser->request(
            method: 'POST',
            uri: sprintf('/api/v2/shop/orders/%s/payment-requests', $order->getTokenValue()),
            server: $this->headerBuilder()
                ->withJsonLdAccept()
                ->withJsonLdContentType()
                ->withShopUserAuthorization('oliver@doe.com')
                ->build(),
            content: json_encode([
                'paymentId' => $payment->getId(),
                'paymentMethodCode' => $paymentMethod->getCode(),
                'payload' => [
                    'success_url' => 'https://myshop.tld/target-path',
                    'cancel_url' => 'https://myshop.tld/after-path',
                ],
            ], \JSON_THROW_ON_ERROR),
        );

        $this->assertResponse(
            $kernelBrowser->getResponse(),
            $responsePath,
            Response::HTTP_CREATED,
        );
    }

    /**
     * @dataProvider createPaymentRequestProviderWithError
     *
     * @param string[] $fixturesPaths
     *
     * @throws \JsonException
     */
    public function test_it_does_not_create_a_payment_request_without_required_data(string $method, array $fixturesPaths, string $responsePath): void
    {
        $fixtures = $this->loadFixturesFromFiles($fixturesPaths);

        /** @var OrderInterface $order */
        $order = $fixtures['order'];
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $fixtures[$method];
        /** @var PaymentInterface $payment */
        $payment = $fixtures['payment'];

        $kernelBrowser = $this->client;
        Assert::notNull($kernelBrowser);

        $kernelBrowser->request(
            method: 'POST',
            uri: sprintf('/api/v2/shop/orders/%s/payment-requests', $order->getTokenValue()),
            server: $this->headerBuilder()
                ->withJsonLdAccept()
                ->withJsonLdContentType()
                ->withShopUserAuthorization('oliver@doe.com')
                ->build(),
            content: json_encode([
                'paymentId' => $payment->getId(),
                'paymentMethodCode' => $paymentMethod->getCode(),
            ], \JSON_THROW_ON_ERROR),
        );

        $this->assertResponse(
            $kernelBrowser->getResponse(),
            $responsePath,
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    /**
     * @return iterable<array{string, string[], string}>
     */
    public static function createPaymentRequestProvider(): iterable
    {
        foreach (['payment_method_stripe_checkout', 'payment_method_stripe_checkout_authorize'] as $method) {
            yield $method => [
                $method,
                [
                    'shop_user.yaml',
                    'channel.yaml',
                    'customer.yaml',
                    'payment.yaml',
                    'payment_method.yaml',
                    'product_variant.yaml',
                    'shipping_category.yaml',
                    'tax_category.yaml',
                    'shipping_method.yaml',
                    'order_awaiting_payment.yaml',
                ],
                'shop/payment_request/post_payment_request_'.$method,
            ];
        }
    }

    /**
     * @return iterable<array{string, string[], string}>
     */
    public static function createPaymentRequestProviderWithError(): iterable
    {
        foreach (['payment_method_stripe_checkout', 'payment_method_stripe_checkout_authorize'] as $method) {
            yield $method => [
                $method,
                [
                    'shop_user.yaml',
                    'channel.yaml',
                    'customer.yaml',
                    'payment.yaml',
                    'payment_method.yaml',
                    'product_variant.yaml',
                    'shipping_category.yaml',
                    'tax_category.yaml',
                    'shipping_method.yaml',
                    'order_awaiting_payment.yaml',
                ],
                'shop/payment_request/post_payment_request_with_error',
            ];
        }
    }
}
