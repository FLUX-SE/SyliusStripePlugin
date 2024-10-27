<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\OrderPay\Provider\WebElements;

use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FluxSE\SyliusStripePlugin\Provider\AfterUrlTypeEnum;
use Stripe\PaymentIntent;
use Sylius\Bundle\PaymentBundle\Provider\HttpResponseProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class CaptureHttpResponseProvider implements HttpResponseProviderInterface
{
    public function __construct(
        private AfterUrlProviderInterface $afterUrlProvider,
        private Environment $twig,
    ) {
    }

    public function supports(
        RequestConfiguration $requestConfiguration,
        PaymentRequestInterface $paymentRequest,
    ): bool {
        return $paymentRequest->getAction() === PaymentRequestInterface::ACTION_CAPTURE;
    }

    public function getResponse(
        RequestConfiguration $requestConfiguration,
        PaymentRequestInterface $paymentRequest,
    ): Response {
        $data = $paymentRequest->getResponseData();

        /** @var string|null $object */
        $object = $data['object'] ?? 'null';
        if (PaymentIntent::OBJECT_NAME !== $object) {
            throw new \LogicException(sprintf(
                'No payment intent object found, "%s" object found instead.',
                $object,
            ));
        }

        return new Response(
            $this->twig->render(
                '@FluxSESyliusStripePlugin/shop/order_pay/web_elements/capture.html.twig',
                [
                    'publishable_key' => $paymentRequest->getMethod()->getGatewayConfig()?->getConfig()['publishable_key'],
                    'model' => PaymentIntent::constructFrom($data),
                    'action_url' => $this->afterUrlProvider->getUrl($paymentRequest, AfterUrlTypeEnum::ACTION),
                ],
            )
        );
    }
}
