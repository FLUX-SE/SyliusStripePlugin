<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Webmozart\Assert\Assert;

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
        $params['expand'] = $params['expand'] ?? [];

        Assert::isArray($params['expand']);

        foreach ($this->expandFields as $field) {
            $params['expand'][] = $field;
        }
    }
}
