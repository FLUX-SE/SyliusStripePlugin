<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Command\Checkout;

use Sylius\Bundle\PaymentBundle\Command\PaymentRequestHashAwareInterface;
use Sylius\Bundle\PaymentBundle\Command\PaymentRequestHashAwareTrait;

class CancelAuthorizedPaymentRequest implements PaymentRequestHashAwareInterface
{
    use PaymentRequestHashAwareTrait;

    public function __construct(protected ?string $hash)
    {
    }
}
