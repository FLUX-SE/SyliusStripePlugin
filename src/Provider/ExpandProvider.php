<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements InnerParamsProviderInterface<StripeObject>
 */
final readonly class ExpandProvider implements InnerParamsProviderInterface
{
    /**
     * @param string[] $expandFields
     */
    public function __construct(
        private array $expandFields,
    ) {
    }

    public function provide(PaymentRequestInterface $paymentRequest, array &$params): void
    {
        if (false === isset($params['expand'])) {
            $params['expand'] = [];
        }

        if (false === is_array($params['expand'])) {
            return;
        }

        foreach ($this->expandFields as $field) {
            $params['expand'][] = $field;
        }
    }
}
