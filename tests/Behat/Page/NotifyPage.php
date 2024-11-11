<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Page;

use Behat\Mink\Session;
use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;
use Symfony\Component\Routing\RouterInterface;

class NotifyPage extends SymfonyPage implements NotifyPageInterface
{
    /**
     * @param array<array-key, mixed> $minkParameters
     */
    public function __construct(
        Session $session,
        $minkParameters,
        RouterInterface $router,
        private string $routeName
    ) {
        parent::__construct($session, $minkParameters, $router);
    }

    public function getNotifyUrl(array $urlParameters): string
    {
        return $this->getUrl($urlParameters);
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }
}
