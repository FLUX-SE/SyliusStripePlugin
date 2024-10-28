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
    /**
     * @var array<string, string>
     */
    private array $savedAppInfo = [];

    private null|LoggerInterface|StripeLoggerInterface $savedLogger = null;
    private ClientInterface $savedHttpClient;

    private StreamingClientInterface $savedStreamingHttpClient;

    public function __construct(
        private LoggerInterface $logger,
        private ClientInterface $httpClient,
        private StreamingClientInterface $streamingHttpClient,
    ) {
        $this->savedHttpClient = CurlClient::instance();
        $this->savedStreamingHttpClient = CurlClient::instance();
    }

    public function configure(array $config): void
    {
        $this->savedAppInfo = Stripe::getAppInfo() ?? [];
        $this->savedLogger = Stripe::getLogger();

        $package = 'flux-se/stripe-plugin-stripe-plugin';
        if (InstalledVersions::isInstalled($package)) {
            $version = InstalledVersions::getVersion($package);
        } else {
            $version = InstalledVersions::getRootPackage()['version'];
        }
        Stripe::setAppInfo("FluxSESyliusStripePlugin", $version, "https://github.com/FLUX-SE/SyliusStripePlugin");

        Stripe::setLogger($this->logger);

        ApiRequestor::setHttpClient($this->httpClient);
        ApiRequestor::setStreamingHttpClient($this->streamingHttpClient);
    }

    public function unConfigure(): void
    {
        Stripe::$appInfo = $this->savedAppInfo;

        // Stripe::$logger accepts null|LoggerInterface
        // but Stripe::setLogger() accepts LoggerInterface|StripeLoggerInterface
        if (null === $this->savedLogger) {
            Stripe::$logger = $this->savedLogger;
        } else {
            Stripe::setLogger($this->savedLogger);
        }

        ApiRequestor::setHttpClient($this->savedHttpClient);
        ApiRequestor::setStreamingHttpClient($this->savedStreamingHttpClient);
    }
}
