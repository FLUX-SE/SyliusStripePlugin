<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Page\External;

use FriendsOfBehat\PageObjectExtension\Page\PageInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\Response;

interface StripePageInterface extends PageInterface
{
    public function captureOrAuthorize(): void;

    public function endCaptureOrAuthorize(): void;

    public function notify(string $payload): Response;

    public function findLatestPaymentRequest(): PaymentRequestInterface;
}
