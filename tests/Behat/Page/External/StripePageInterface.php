<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Page\External;

use FriendsOfBehat\PageObjectExtension\Page\PageInterface;

interface StripePageInterface extends PageInterface
{
    public function captureOrAuthorizeThenGoToAfterUrl(): void;

    public function notify(string $content): void;
}
