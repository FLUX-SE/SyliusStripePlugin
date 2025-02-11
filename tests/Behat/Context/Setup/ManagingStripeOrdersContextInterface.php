<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Sylius\Component\Core\Model\OrderInterface;

interface ManagingStripeOrdersContextInterface extends Context
{
    public function thisOrderIsAlreadyPaidUsingStripe(OrderInterface $order): void;

    public function thisOrderIsAlreadyAuthorizedUsingStripe(OrderInterface $order): void;

    public function thisOrderIsNotYetPaidUsingStripe(OrderInterface $order): void;

    public function thisOrderPaymentHasBeenCancelled(OrderInterface $order): void;

    public function iAmPreparedToCaptureAuthorizationOfThisOrder(OrderInterface $order): void;

    public function iAmPreparedToCancelThisOrder(OrderInterface $order): void;

    public function iAmPreparedToCancelAuthorizationOnThisOrder(OrderInterface $order): void;

    public function iAmPreparedToRefundThisOrder(): void;
}
