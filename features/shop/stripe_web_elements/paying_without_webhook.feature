@paying_with_stripe_web_elements_during_checkout
Feature: Paying with Stripe JS during checkout without webhook
    In order to buy products
    As a Customer
    I want to be able to pay with "Stripe JS" payment gateway without webhooks

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a payment method "Stripe" with a code "stripe" and Stripe Web Elements payment gateway
        And the store allows paying "Offline"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for Free
        And I am a logged in customer
        And I had product "PHP T-Shirt" in the cart
        And I have proceeded selecting "Stripe" payment method

    @ui @api @javascript
    Scenario: Successful payment in Stripe without webhooks
        When I confirm my order with Stripe payment
        And The Stripe JS form is displayed and I complete the payment without webhook
        Then I should see the thank you page

    @ui @api @javascript
    Scenario: Cancelling the payment without webhooks
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Stripe" payment method
        When I confirm my order with Stripe payment
        And I click on "go back" during my Stripe payment
        Then I should be able to pay again

    @ui @api @javascript
    Scenario: Retrying the payment with success without webhooks
        Given I have confirmed my order with Stripe payment
        But I have clicked on "go back" during my Stripe payment
        When I try to pay again with Stripe payment
        And The Stripe JS form is displayed and I complete the payment without webhook
        Then I should see the thank you page

    @ui @api @javascript
    Scenario: Retrying the payment and failing without webhooks
        Given I have confirmed my order with Stripe payment
        But I have clicked on "go back" during my Stripe payment
        When I try to pay again with Stripe payment
        And I click on "go back" during my Stripe payment
        Then I should be able to pay again