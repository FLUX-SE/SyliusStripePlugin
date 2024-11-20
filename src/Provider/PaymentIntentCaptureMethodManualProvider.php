<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements InnerParamsProviderInterface<PaymentIntent>
 */
final class PaymentIntentCaptureMethodManualProvider implements InnerParamsProviderInterface
{
    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        if (false === ($paymentRequest->getMethod()->getGatewayConfig()?->getConfig()['use_authorize'] ?? false)) {
            return;
        }

        $params['capture_method'] = PaymentIntent::CAPTURE_METHOD_MANUAL;
    }
}
