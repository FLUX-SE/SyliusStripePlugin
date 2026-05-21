<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\OrderPay\Provider\WebElements;

use FluxSE\SyliusStripePlugin\OrderPay\Provider\WebElements\CaptureHttpResponseProvider;
use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use PHPUnit\Framework\TestCase;
use Stripe\PaymentIntent;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class CaptureHttpResponseProviderTest extends TestCase
{
    public function test_it_passes_customer_email_to_template_when_order_has_customer_with_email(): void
    {
        $context = $this->captureRenderContext(customerEmail: 'oliver@doe.com');

        self::assertSame('oliver@doe.com', $context['customer_email']);
    }

    public function test_it_passes_null_email_when_customer_has_no_email(): void
    {
        $context = $this->captureRenderContext(customerEmail: null);

        self::assertNull($context['customer_email']);
    }

    public function test_it_passes_null_email_when_order_has_no_customer(): void
    {
        $context = $this->captureRenderContext(customerEmail: null, omitCustomer: true);

        self::assertNull($context['customer_email']);
    }

    public function test_it_passes_publishable_key_and_action_url_and_model_to_template(): void
    {
        $context = $this->captureRenderContext(customerEmail: 'oliver@doe.com');

        self::assertSame('pk_test_xyz', $context['publishable_key']);
        self::assertSame('https://example.com/after', $context['action_url']);
        self::assertInstanceOf(PaymentIntent::class, $context['model']);
        self::assertSame('pi_test_secret', $context['model']->client_secret);
    }

    /** @return array<string, mixed> */
    private function captureRenderContext(?string $customerEmail, bool $omitCustomer = false): array
    {
        if ($omitCustomer) {
            $customer = null;
        } else {
            $customer = $this->createMock(CustomerInterface::class);
            $customer->method('getEmail')->willReturn($customerEmail);
        }

        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn($customer);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getDetails')->willReturn(['client_secret' => 'pi_test_secret']);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);
        $paymentRequest->method('getResponseData')->willReturn(['publishable_key' => 'pk_test_xyz']);

        $afterUrlProvider = $this->createMock(AfterUrlProviderInterface::class);
        $afterUrlProvider->method('getUrl')
            ->with($paymentRequest, AfterUrlProviderInterface::ACTION_URL)
            ->willReturn('https://example.com/after');

        $capturedContext = null;
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('@FluxSESyliusStripePlugin/shop/order_pay/web_elements/capture.html.twig')
            ->willReturnCallback(function (string $template, array $context) use (&$capturedContext): string {
                $capturedContext = $context;

                return '';
            });

        $provider = new CaptureHttpResponseProvider($afterUrlProvider, $twig);

        $response = $provider->getResponse($this->createMock(RequestConfiguration::class), $paymentRequest);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($capturedContext);

        return $capturedContext;
    }
}
