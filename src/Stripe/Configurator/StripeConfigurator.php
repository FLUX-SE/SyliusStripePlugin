<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\Configurator;

use Composer\InstalledVersions;
use Psr\Log\LoggerInterface;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\HttpClient\StreamingClientInterface;
use Stripe\Stripe;
use Stripe\Util\LoggerInterface as StripeLoggerInterface;

final class StripeConfigurator implements StripeConfiguratorInterface
{
    private ?array $savedAppInfo = null;

    private null|LoggerInterface|StripeLoggerInterface $savedLogger = null;
    private ?ClientInterface $savedHttpClient = null;

    private ?StreamingClientInterface $savedStreamingHttpClient = null;

    public function __construct(
        private LoggerInterface $logger,
        private ClientInterface $httpClient,
        private StreamingClientInterface $streamingHttpClient,
    ) {}

    public function configure(array $config): void
    {
        $this->savedAppInfo = Stripe::getAppInfo();
        $this->savedHttpClient = CurlClient::instance();
        $this->savedStreamingHttpClient = CurlClient::instance();
        $this->savedLogger = Stripe::getLogger();

        $package = 'flux-se/stripe-plugin-stripe-plugin';
        if (InstalledVersions::isInstalled($package)) {
            $version = InstalledVersions::getVersion($package);
        } else {
            $version = InstalledVersions::getRootPackage()['version'] ?? '';
        }
        Stripe::setAppInfo("FluxSESyliusStripePlugin", $version, "https://github.com/FLUX-SE/SyliusStripePlugin");

        Stripe::setLogger($this->logger);

        ApiRequestor::setHttpClient($this->httpClient);
        ApiRequestor::setStreamingHttpClient($this->streamingHttpClient);
    }

    public function unConfigure(): void
    {
        if (null === $this->savedAppInfo) {
            Stripe::$appInfo = null;
        }

        Stripe::setAppInfo(
            $this->savedAppInfo['name'],
            $this->savedAppInfo['version'],
            $this->savedAppInfo['url'],
            $this->savedAppInfo['partner_id'],
        );

        Stripe::setLogger($this->savedLogger);

        ApiRequestor::setHttpClient($this->savedHttpClient);
        ApiRequestor::setStreamingHttpClient($this->savedStreamingHttpClient);
    }
}
