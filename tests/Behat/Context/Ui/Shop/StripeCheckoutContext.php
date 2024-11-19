<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Ui\Shop;

use Behat\MinkExtension\Context\MinkContext;
use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Event;
use Sylius\Behat\Page\Shop\Checkout\CompletePageInterface;
use Sylius\Behat\Page\Shop\Order\ShowPageInterface;
use Tests\FluxSE\SyliusStripePlugin\Behat\Page\External\StripePage;
use Tests\FluxSE\SyliusStripePlugin\Mocker\StripeCheckoutMocker;

class StripeCheckoutContext extends MinkContext
{
    public function __construct(
        private StripeCheckoutMocker $stripeCheckoutSessionMocker,
        private CompletePageInterface $summaryPage,
        private ShowPageInterface $orderDetails,
        private StripePage $stripePage,
    ) {
    }

    /**
     * @Given I have confirmed my order with Stripe payment
     * @When I confirm my order with Stripe payment
     */
    public function iConfirmMyOrderWithStripePayment(): void
    {
        $this->stripeCheckoutSessionMocker->mockCaptureOrAuthorize(function () {
            $this->summaryPage->confirmOrder();
        });
    }

    /**
     * @When I get redirected to Stripe and complete my payment
     */
    public function iGetRedirectedToStripe(): void
    {
        $this->stripeCheckoutSessionMocker->mockSuccessfulPayment(
            function () {
                $jsonEvent = [
                    'id' => 'evt_test_1',
                    'type' => Event::CHECKOUT_SESSION_COMPLETED,
                    'object' => 'event',
                    'data' => [
                        'object' => [
                            'id' => 'cs_test_1',
                            'object' => Session::OBJECT_NAME,
                            'payment_intent' => 'pi_test_1',
                            'metadata' => [
                                MetadataProviderInterface::DEFAULT_TOKEN_HASH_KEY_NAME => '%s',
                            ],
                        ],
                    ],
                ];
                $payload = json_encode($jsonEvent, \JSON_THROW_ON_ERROR);

                $this->stripePage->notify($payload);
            },
            function () {
                $this->stripePage->captureOrAuthorizeThenGoToAfterUrl();
            },
        );
    }

    /**
     * @When I get redirected to Stripe and complete my payment using authorize
     */
    public function iGetRedirectedToStripeUsingAuthorize(): void
    {
        $this->stripeCheckoutSessionMocker->mockAuthorizePayment(
            function () {
                $jsonEvent = [
                    'id' => 'evt_test_1',
                    'type' => Event::CHECKOUT_SESSION_COMPLETED,
                    'object' => 'event',
                    'data' => [
                        'object' => [
                            'id' => 'cs_test_1',
                            'object' => Session::OBJECT_NAME,
                            'payment_intent' => 'pi_test_1',
                            'metadata' => [
                                MetadataProviderInterface::DEFAULT_TOKEN_HASH_KEY_NAME => '%s',
                            ],
                        ],
                    ],
                ];
                $payload = json_encode($jsonEvent, \JSON_THROW_ON_ERROR);

                $this->stripePage->notify($payload);
            },
            function () {
                $this->stripePage->captureOrAuthorizeThenGoToAfterUrl();
            },
        );
    }

    /**
     * @When I get redirected to Stripe and complete my payment without webhook
     */
    public function iGetRedirectedToStripeWithoutWebhooks(): void
    {
        $this->stripeCheckoutSessionMocker->mockSuccessfulPaymentWithoutWebhook(function () {
            $this->stripePage->captureOrAuthorizeThenGoToAfterUrl();
        });
    }

    /**
     * @When I get redirected to Stripe and complete my payment without webhook using authorize
     */
    public function iGetRedirectedToStripeWithoutWebhookUsingAuthorize(): void
    {
        $this->stripeCheckoutSessionMocker->mockSuccessfulPaymentWithoutWebhookUsingAuthorize(function () {
            $this->stripePage->captureOrAuthorizeThenGoToAfterUrl();
        });
    }

    /**
     * @Given I have clicked on "go back" during my Stripe payment
     * @When I click on "go back" during my Stripe payment
     */
    public function iClickOnGoBackDuringMyStripePayment(): void
    {
        $this->stripeCheckoutSessionMocker->mockGoBackPayment(function () {
            $this->stripePage->captureOrAuthorizeThenGoToAfterUrl();
        });
    }

    /**
     * @When I try to pay again with Stripe payment
     */
    public function iTryToPayAgainWithStripePayment(): void
    {
        $this->stripeCheckoutSessionMocker->mockCaptureOrAuthorize(function () {
            $this->orderDetails->pay();
        });
    }

    /**
     * @Then I should be notified that my payment has been authorized
     */
    public function iShouldBeNotifiedThatMyPaymentHasBeenAuthorized(): void
    {
        $this->assertNotification('Payment has been authorized.');
    }

    private function assertNotification(string $expectedNotification): void
    {
        $notifications = $this->orderDetails->getNotifications();
        $hasNotifications = '';

        foreach ($notifications as $notification) {
            $hasNotifications .= $notification;
            if ($notification === $expectedNotification) {
                return;
            }
        }

        throw new RuntimeException(sprintf('There is no notification with "%s". Got "%s"', $expectedNotification, $hasNotifications));
    }
}
