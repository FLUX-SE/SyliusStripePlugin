<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Ui\Shop;

use Behat\MinkExtension\Context\MinkContext;
use FluxSE\SyliusStripePlugin\Provider\MetadataProviderInterface;
use RuntimeException;
use Stripe\Event;
use Stripe\PaymentIntent;
use Sylius\Behat\Page\Shop\Checkout\CompletePageInterface;
use Sylius\Behat\Page\Shop\Order\ShowPageInterface;
use Tests\FluxSE\SyliusStripePlugin\Behat\Page\External\StripePage;
use Tests\FluxSE\SyliusStripePlugin\Mocker\StripeWebElementsMocker;

class StripeWebElementsContext extends MinkContext
{
    public function __construct(
        private StripeWebElementsMocker $stripeJsMocker,
        private CompletePageInterface $summaryPage,
        private ShowPageInterface $orderDetails,
        private StripePage $stripePage,
    ) {
    }

    /**
     * @When The Stripe JS form is displayed and I complete the payment
     */
    public function theStripeJsFormIsDisplayedAndICompleteThePayment(): void
    {
        $this->stripeJsMocker->mockSuccessfulPayment(
            function () {
                $jsonEvent = [
                    'id' => 'evt_test_1',
                    'type' => Event::PAYMENT_INTENT_SUCCEEDED,
                    'object' => 'event',
                    'data' => [
                        'object' => [
                            'id' => 'pi_test_1',
                            'object' => PaymentIntent::OBJECT_NAME,
                            'capture_method' => PaymentIntent::CAPTURE_METHOD_AUTOMATIC,
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
     * @When The Stripe JS form is displayed and I complete the payment using authorize
     */
    public function theStripeJsFormIsDisplayedAndICompleteThePaymentUsingAuthorize(): void
    {
        $this->stripeJsMocker->mockAuthorizePayment(
            function () {
                $jsonEvent = [
                    'id' => 'evt_test_1',
                    'type' => Event::PAYMENT_INTENT_SUCCEEDED,
                    'object' => 'event',
                    'data' => [
                        'object' => [
                            'id' => 'pi_test_1',
                            'object' => PaymentIntent::OBJECT_NAME,
                            'capture_method' => PaymentIntent::CAPTURE_METHOD_MANUAL,
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
     * @When The Stripe JS form is displayed and I complete the payment without webhook
     */
    public function theStripeJsFormIsDisplayedAndICompleteThePaymentWithoutWebhooks(): void
    {
        $this->stripeJsMocker->mockSuccessfulPaymentWithoutWebhook(function () {
            $this->stripePage->captureOrAuthorizeThenGoToAfterUrl();
        });
    }

    /**
     * @When The Stripe JS form is displayed and I complete the payment without webhook using authorize
     */
    public function theStripeJsFormIsDisplayedAndICompleteThePaymentWithoutWebhookUsingAuthorize(): void
    {
        $this->stripeJsMocker->mockSuccessfulPaymentWithoutWebhookUsingAuthorize(function () {
            $this->stripePage->captureOrAuthorizeThenGoToAfterUrl();
        });
    }

    /**
     * @Given I have confirmed my order with Stripe payment
     * @When I confirm my order with Stripe payment
     */
    public function iConfirmMyOrderWithStripePayment(): void
    {
        $this->stripeJsMocker->mockCaptureOrAuthorize(function () {
            $this->summaryPage->confirmOrder();
        });
    }

    /**
     * @Given I have clicked on "go back" during my Stripe payment
     * @When I click on "go back" during my Stripe payment
     */
    public function iClickOnGoBackDuringMyStripePayment(): void
    {
        $this->stripeJsMocker->mockGoBackPayment(function () {
            $this->stripePage->captureOrAuthorizeThenGoToAfterUrl();
        });
    }

    /**
     * @When I try to pay again with Stripe payment
     */
    public function iTryToPayAgainWithStripePayment(): void
    {
        $this->stripeJsMocker->mockCaptureOrAuthorize(function () {
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
