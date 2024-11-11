@managing_payment_methods
Feature: Adding a new  Stripe Checkout Session payment method
    In order to allow payment for orders, using the Stripe gateway
    As an Administrator
    I want to add new payment methods to the system

    Background:
        Given the store operates on a single channel in "United States"
        And I am logged in as an administrator

    @api @ui @javascript
    Scenario: Adding a new stripe payment method using authorize
        When I want to create a new payment method with "Stripe Checkout" gateway factory
        And I name it "Stripe Checkout (authorize)" in "English (United States)"
        And I specify its code as "stripe_checkout_authorize_test"
        And I configure it with test stripe gateway data "TEST" and "TEST"
        And I add a webhook secret key "TEST"
        And I use authorize
        And I add it
        Then I should be notified that it has been successfully created
        And I should see a warning message under the use authorize field
        And the payment method "Stripe Checkout (authorize)" should appear in the registry

    @api @ui @javascript
    Scenario: Adding a new stripe payment method not using authorize
        When I want to create a new payment method with "Stripe Checkout" gateway factory
        And I name it "Stripe Checkout" in "English (United States)"
        And I specify its code as "stripe_checkout_test"
        And I configure it with test stripe gateway data "TEST" and "TEST"
        And I add a webhook secret key "TEST"
        And I don't use authorize
        And I add it
        Then I should be notified that it has been successfully created
        And I should not see a warning message under the use authorize field
        And the payment method "Stripe Checkout" should appear in the registry
