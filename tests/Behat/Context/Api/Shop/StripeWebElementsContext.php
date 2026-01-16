<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Api\Shop;

use Behat\MinkExtension\Context\MinkContext;
use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use Stripe\Event;
use Stripe\PaymentIntent;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Context\Api\Shop\CheckoutContext;
use Sylius\Behat\Context\Api\Shop\PaymentRequestContext;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeWebElementsMocker;
use Tests\FluxSE\SyliusStripePlugin\Behat\Page\External\StripePage;
use Webmozart\Assert\Assert;

class StripeWebElementsContext extends MinkContext implements StripeContextInterface
{
    public function __construct(
        private readonly SharedStorageInterface $sharedStorage,
        private readonly CheckoutContext $checkoutContext,
        private readonly PaymentRequestContext $paymentRequestContext,
        private StripeWebElementsMocker $stripeWebElementsMocker,
        private StripePage $stripePage,
        private ApiClientInterface $client,
    ) {
    }

    /**
     * @Given I have confirmed my order with Stripe payment
     * @Given I have confirmed my order with Stripe payment using authorize
     * @When I confirm my order with Stripe payment
     * @When I confirm my order with Stripe payment using authorize
     */
    public function iConfirmMyOrderWithStripePayment(): void
    {
        $this->checkoutContext->iConfirmMyOrder();

        $this->iTryToPayAgainWithStripePayment();
    }

    /**
     * @When I try to pay again with Stripe payment
     * @When I try to pay again with Stripe payment using authorize
     */
    public function iTryToPayAgainWithStripePayment(): void
    {
        $this->stripeWebElementsMocker->mockCaptureOrAuthorize();

        $this->paymentRequestContext->iTryToPayForMyOrder([
            'success_url' => 'https://myshop.tld/target-path',
            'cancel_url' => 'https://myshop.tld/after-path',
        ]);
    }

    /**
     * @When I complete my Stripe payment successfully
     */
    public function iCompleteMyStripePaymentSuccessfully(): void
    {
        $paymentRequest = $this->stripePage->findLatestPaymentRequest();

        $jsonEvent = [
            'id' => 'evt_test_1',
            'object' => Event::OBJECT_NAME,
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            'data' => [
                'object' => [
                    'id' => 'pi_test_1',
                    'object' => PaymentIntent::OBJECT_NAME,
                    'status' => PaymentIntent::STATUS_SUCCEEDED,
                    'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
                    'metadata' => [
                        MetadataProviderInterface::DEFAULT_TOKEN_HASH_KEY_NAME => $paymentRequest->getId(),
                    ],
                ],
            ],
        ];

        $this->stripeWebElementsMocker->mockWebhookHandling($jsonEvent);

        $payload = json_encode($jsonEvent, \JSON_THROW_ON_ERROR);

        $response = $this->stripePage->notify($payload);
        $this->assertNotifySucceeded($response);

        $this->stripeWebElementsMocker->mockSuccessfulPayment();

        $this->stripePage->endCaptureOrAuthorize();
    }

    /**
     * @When I complete my Stripe payment successfully without webhook
     */
    public function iCompleteMyStripePaymentSuccessfullyWithoutWebhooks(): void
    {
        $this->stripeWebElementsMocker->mockSuccessfulPayment();

        $this->stripePage->endCaptureOrAuthorize();
    }

    /**
     * @When I complete my Stripe payment successfully using authorize
     */
    public function iCompleteMyStripePaymentSuccessfullyUsingAuthorize(): void
    {
        $paymentRequest = $this->stripePage->findLatestPaymentRequest();

        $jsonEvent = [
            'id' => 'evt_test_1',
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            'object' => 'event',
            'data' => [
                'object' => [
                    'id' => 'pi_test_1',
                    'object' => PaymentIntent::OBJECT_NAME,
                    'status' => PaymentIntent::STATUS_REQUIRES_CAPTURE,
                    'capture_method' => PaymentIntent::CAPTURE_METHOD_MANUAL,
                    'metadata' => [
                        MetadataProviderInterface::DEFAULT_TOKEN_HASH_KEY_NAME => $paymentRequest->getId(),
                    ],
                ],
            ],
        ];

        $this->stripeWebElementsMocker->mockWebhookHandling($jsonEvent);

        $payload = json_encode($jsonEvent, \JSON_THROW_ON_ERROR);

        $response = $this->stripePage->notify($payload);
        $this->assertNotifySucceeded($response);

        $this->stripeWebElementsMocker->mockAuthorizePayment();

        $this->stripePage->endCaptureOrAuthorize();
    }

    /**
     * @When I complete my Stripe payment successfully without webhook using authorize
     */
    public function iCompleteMyStripePaymentSuccessfullyWithoutWebhookUsingAuthorize(): void
    {
        $this->stripeWebElementsMocker->mockAuthorizePayment();

        $this->stripePage->captureOrAuthorize();
    }

    /**
     * @Given I have clicked on "go back" during my Stripe payment
     * @When I click on "go back" during my Stripe payment
     */
    public function iCancelMyStripePayment(): void
    {
        $this->stripeWebElementsMocker->mockGoBackPayment();

        $uri = $this->sharedStorage->get('payment_request_uri');
        $uri = preg_replace('#^(.+)/(payment-requests/.+$)#u', '$2', $uri);

        $this->client->buildCustomUpdateRequest($uri)->update();
    }

    /**
     * @Then I should be notified that my payment has been authorized
     */
    public function iShouldBeNotifiedThatMyPaymentHasBeenAuthorized(): void
    {
        /** @var OrderInterface $order */
        $order = $this->sharedStorage->get('order');

        /** @var PaymentMethodInterface|null $paymentMethod */
        $paymentMethod = $order->getLastPayment()?->getMethod();
        Assert::notNull($paymentMethod);

        $this->paymentRequestContext->aPaymentRequestWithActionForPaymentMethodShouldHaveState(
            PaymentRequestInterface::ACTION_AUTHORIZE,
            $paymentMethod,
            PaymentRequestInterface::STATE_COMPLETED,
        );
    }

    private function assertNotifySucceeded(Response $response): void
    {
        if (Response::HTTP_NO_CONTENT === $response->getStatusCode()) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'The response status code should be 204, but got %s with content: %s',
            $response->getStatusCode(),
            $response->getContent(),
        ));
    }
}
