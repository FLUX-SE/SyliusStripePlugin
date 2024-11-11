<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\StateMachine;

use Sylius\Component\Core\Model\PaymentInterface;

interface PaymentStateProcessorInterface
{
    public function __invoke(PaymentInterface $payment, string $fromState): void;
}
