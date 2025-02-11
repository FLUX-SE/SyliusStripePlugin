<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Command\Checkout;

use Sylius\Bundle\PaymentBundle\Command\PaymentRequestHashAwareInterface;
use Sylius\Bundle\PaymentBundle\Command\PaymentRequestHashAwareTrait;

class CompleteAuthorizedPaymentRequest implements PaymentRequestHashAwareInterface
{
    use PaymentRequestHashAwareTrait;

    public function __construct(protected ?string $hash)
    {
    }
}
