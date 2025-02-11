<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Ui\Shop;

interface StripeContextInterface
{
    public function iConfirmMyOrderWithStripePayment(): void;

    public function iTryToPayAgainWithStripePayment(): void;

    public function iCompleteMyStripePaymentSuccessfully(): void;

    public function iCompleteMyStripePaymentSuccessfullyWithoutWebhooks(): void;

    public function iCompleteMyStripePaymentSuccessfullyUsingAuthorize(): void;

    public function iCompleteMyStripePaymentSuccessfullyWithoutWebhookUsingAuthorize(): void;

    public function iCancelMyStripePayment(): void;

    public function iShouldBeNotifiedThatMyPaymentHasBeenAuthorized(): void;
}
