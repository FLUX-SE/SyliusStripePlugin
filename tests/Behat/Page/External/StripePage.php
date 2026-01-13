<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Page\External;

use ArrayAccess;
use Behat\Mink\Session;
use FriendsOfBehat\PageObjectExtension\Page\Page;
use Sylius\Bundle\CoreBundle\OrderPay\Provider\UrlProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
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
        private RouterInterface $router,
        private UrlProviderInterface $urlProvider,
    ) {
        parent::__construct($session, $minkParameters);
    }

    public function captureOrAuthorize(): void
    {
        $paymentRequest = $this->findLatestPaymentRequest();

        /** @var string|null $url */
        $url = $paymentRequest->getResponseData()['url'] ?? null;
        if (null === $url) {
            /** @var PaymentInterface $payment */
            $payment = $paymentRequest->getPayment();
            $this->router->getContext()->setParameter('_locale', $payment->getOrder()?->getLocaleCode());
            $url = $this->urlProvider->getUrl($paymentRequest);
        }

        $this->getSession()->visit($url);
    }

    public function endCaptureOrAuthorize(): void
    {
        $paymentRequest = $this->findLatestPaymentRequest();

        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();
        $this->router->getContext()->setParameter('_locale', $payment->getOrder()?->getLocaleCode());
        $url = $this->urlProvider->getUrl($paymentRequest, UrlGeneratorInterface::RELATIVE_PATH);

        $this->getSession()->visit($url);
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

    public function notify(string $payload): Response
    {
        $paymentRequest = $this->findLatestPaymentRequest();

        $code = $paymentRequest->getMethod()->getCode();
        Assert::notNull($code);

        $notifyUrl = $this->notifyPage->getNotifyUrl([
            'code' => $code,
        ]);

        $this->client->request(
            'POST',
            $notifyUrl,
            [],
            [],
            $this->generateSignature($payload),
            $payload,
        );

        return $this->client->getResponse();
    }

    public function findLatestPaymentRequest(): PaymentRequestInterface
    {
        // Allow to wait for PaymentRequest to be processed
        sleep(2);

        $paymentRequests = $this->paymentRequestRepository->findBy(
            [
                'state' => [
                    PaymentRequestInterface::STATE_PROCESSING,
                ],
                'action' => [
                        PaymentRequestInterface::ACTION_CAPTURE,
                        PaymentRequestInterface::ACTION_AUTHORIZE,
                ],
            ],
            [
                'createdAt' => 'DESC',
            ],
            1,
        );

        $paymentRequest = $paymentRequests[0] ?? null;
        Assert::notNull($paymentRequest, 'Unable to find the latest payment request');

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
