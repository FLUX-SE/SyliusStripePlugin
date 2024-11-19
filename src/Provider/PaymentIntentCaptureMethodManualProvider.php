<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements DetailsProviderInterface<PaymentIntent>
 */
final class PaymentIntentCaptureMethodManualProvider implements DetailsProviderInterface
{
    public function getDetails(PaymentRequestInterface $paymentRequest, array &$details): void
    {
        if (false === ($paymentRequest->getMethod()->getGatewayConfig()?->getConfig()['use_authorize'] ?? false)) {
            return;
        }

        $details['capture_method'] = PaymentIntent::CAPTURE_METHOD_MANUAL;
    }
}
