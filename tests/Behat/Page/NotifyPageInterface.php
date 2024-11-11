<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Page;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPageInterface;

interface NotifyPageInterface extends SymfonyPageInterface
{
    /**
     * @param array<string, string|bool|int|float> $urlParameters
     */
    public function getNotifyUrl(array $urlParameters): string;
}
