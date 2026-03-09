<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Twig\Component\AdminPaymentMethod;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

trait FactoryNameTrait
{
    #[LiveProp(fieldName: 'factoryName')]
    public ?string $factoryName = null;
}
