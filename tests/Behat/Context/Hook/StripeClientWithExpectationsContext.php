<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Hook;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Stripe\ApiResource;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

final class StripeClientWithExpectationsContext implements Context
{
    /**
     * @param StripeClientWithExpectationsInterface<ApiResource> $stripeClientWithExpectations
     */
    public function __construct(
        private StripeClientWithExpectationsInterface $stripeClientWithExpectations,
    ) {
    }

    #[BeforeScenario]
    public function resetExpectations(): void
    {
        $this->stripeClientWithExpectations->resetExpectations();
    }

    #[AfterScenario]
    public function hasExpectations(): void
    {
        if ($this->stripeClientWithExpectations->hasExpectations()) {
            throw new \RuntimeException(
                'StripeClientWithExpectations still has expectations left: '
                . json_encode(
                    $this->stripeClientWithExpectations->getExpectations(),
                    \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES,
                ),
            );
        }
    }
}
