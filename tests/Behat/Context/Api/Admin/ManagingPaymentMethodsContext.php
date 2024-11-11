<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Api\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;

class ManagingPaymentMethodsContext implements Context
{
    public function __construct(
        private ApiClientInterface $client,
    ) {
    }

    /**
     * @When I configure it with test stripe gateway data :secretKey and :publishableKey
     */
    public function iConfigureItWithTestStripeGatewayData(string $secretKey, string $publishableKey): void
    {
        $this->updateGatewayConfig([
            'secret_key' => $secretKey,
            'publishable_key' => $publishableKey,
        ]);
    }

    /**
     * @When I add a webhook secret key :webhookKey
     */
    public function iAddAWebhookSecretKey(string $webhookKey): void
    {
        $this->updateGatewayConfig([
            'webhook_secret_keys' => [$webhookKey],
        ]);
    }

    /**
     * @When I use authorize
     */
    public function iUseAuthorize(): void
    {
        $this->updateGatewayConfig([
            'use_authorize' => true,
        ]);
    }

    /**
     * @When I don't use authorize
     */
    public function iDontUseAuthorize(): void
    {
        $this->updateGatewayConfig([
            'use_authorize' => false,
        ]);
    }

    /**
     * @Given /^I should see a warning message under the use authorize field$/
     */
    public function iShouldSeeAWarningMessageUnderTheUseAuthorizeField(): void
    {
        // Not reproductible
    }

    /**
     * @Given /^I should not see a warning message under the use authorize field$/
     */
    public function iShouldNotSeeAWarningMessageUnderTheUseAuthorizeField(): void
    {
        // Not reproductible
    }

    /**
     * @param array<string, string|string[]|bool> $updatedConfig
     */
    private function updateGatewayConfig(array $updatedConfig): void
    {
        /** @var array{gatewayConfig?: array{config?: array<string, string|string[]|bool>}} $content */
        $content = $this->client->getContent();
        $gatewayConfig = $content['gatewayConfig'] ?? [];
        $config = $gatewayConfig['config'] ?? [];
        $config = array_merge($config, $updatedConfig);

        $content['gatewayConfig']['config'] = $config;
        $this->client->updateRequestData($content);
    }
}
