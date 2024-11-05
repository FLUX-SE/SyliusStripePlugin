<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Sylius\Component\Payment\Model\PaymentRequestInterface;

interface MetadataProviderInterface
{
    public const DEFAULT_TOKEN_HASH_KEY_NAME = 'token_hash';

    public const CAPTURE_AUTHORIZE_TOKEN_HASH_KEY_NAME = 'capture_authorize_token_hash';

    public const CANCEL_TOKEN_HASH_KEY_NAME = 'cancel_authorized_token_hash';

    public const REFUND_TOKEN_HASH_KEY_NAME = 'refund_token_hash';

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(PaymentRequestInterface $paymentRequest): array;
}
