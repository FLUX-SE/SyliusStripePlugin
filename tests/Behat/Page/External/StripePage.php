<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Page\External;

use ArrayAccess;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Session;
use FluxSE\SyliusStripePlugin\Provider\AfterUrlProviderInterface;
use FriendsOfBehat\PageObjectExtension\Page\Page;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Tests\FluxSE\SyliusStripePlugin\Behat\Page\NotifyPageInterface;
use Webmozart\Assert\Assert;

final class StripePage extends Page implements StripePageInterface
{
    /**
     * @param array<array-key, mixed>|ArrayAccess<array-key, mixed> $minkParameters
     * @param PaymentRequestRepositoryInterface<PaymentRequestInterface> $paymentRequestRepository
     */
    public function __construct(
        Session $session,
        $minkParameters,
        private PaymentRequestRepositoryInterface $paymentRequestRepository,
        private HttpKernelBrowser $client,
        private NotifyPageInterface $notifyPage,
        private AfterUrlProviderInterface $afterUrlProvider,
    ) {
        parent::__construct($session, $minkParameters);
    }

    /**
     * @throws DriverException
     */
    public function captureOrAuthorizeThenGoToAfterUrl(): void
    {
        $paymentRequest = $this->findLatestPaymentRequest();

        /** @var string|null $url */
        $url = $paymentRequest->getResponseData()['url'];
        if (null === $url) {
            $url = $this->afterUrlProvider->getUrl($paymentRequest, AfterUrlProviderInterface::ACTION_URL);
        }

        $this->getDriver()->visit($url);
    }

    /**
     * @return string[]
     */
    private function generateSignature(string $payload): array
    {
        $now = time();
        $webhookSecretKey = 'whsec_test_1';

        $signedPayload = sprintf('%s.%s', $now, $payload);
        $signature = hash_hmac('sha256', $signedPayload, $webhookSecretKey);

        $sigHeader = sprintf('t=%s,', $now);
        $sigHeader .= sprintf('v1=%s,', $signature);

        return [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
        ];
    }

    public function notify(string $content): void
    {
        $paymentRequest = $this->findLatestPaymentRequest();

        $code = $paymentRequest->getMethod()->getCode();
        Assert::notNull($code);

        $notifyUrl = $this->notifyPage->getNotifyUrl([
            'code' => $code,
        ]);

        $payload = sprintf($content, $paymentRequest->getId());
        $this->client->request(
            'POST',
            $notifyUrl,
            [],
            [],
            $this->generateSignature($payload),
            $payload,
        );
    }

    private function findLatestPaymentRequest(string $state = PaymentRequestInterface::STATE_NEW): PaymentRequestInterface
    {
        $paymentRequests = $this->paymentRequestRepository->findBy(
            ['state' => $state],
            ['createdAt' => 'ASC'],
            1,
        );

        $paymentRequest = $paymentRequests[0] ?? null;
        Assert::notNull($paymentRequest, sprintf('Unable to find the payment request (state: "%s").', $state));

        return $paymentRequest;
    }

    /**
     * @param array<array-key, mixed> $urlParameters
     */
    protected function getUrl(array $urlParameters = []): string
    {
        return 'https://stripe.com';
    }
}
