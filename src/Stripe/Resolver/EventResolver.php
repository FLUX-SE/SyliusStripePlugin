<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Resolver;

use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class EventResolver implements EventResolverInterface
{
    public function resolve(Request $request, PaymentMethodInterface $paymentMethod): Event
    {
        $content = $request->getContent();

        $stripeSignature = $request->headers->get('stripe-signature');
        if (null === $stripeSignature) {
            throw new \LogicException('A Stripe header signature is required.');
        }

        $signatureVerificationErrors = [];
        /** @var string[] $webhookSecretKeys */
        $webhookSecretKeys = $paymentMethod->getGatewayConfig()?->getConfig()['webhook_secret_keys'] ?? [];
        foreach ($webhookSecretKeys as $webhookSecretKey) {
            try {
                return Webhook::constructEvent(
                    $content,
                    $stripeSignature,
                    $webhookSecretKey,
                );
            } catch (SignatureVerificationException $e) {
                $signatureVerificationErrors[] = sprintf(
                    '- Tried with "%s": "%s"',
                    $webhookSecretKey,
                    $e->getMessage()
                );
            }
        }

        $signatureResults = implode(PHP_EOL, $signatureVerificationErrors);
        throw SignatureVerificationException::factory(sprintf(
            'Unable to check Stripe Signature using several webhook keys:%s%s',
            PHP_EOL,
            $signatureResults
        ));
    }
}
