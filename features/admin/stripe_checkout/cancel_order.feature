@managing_stripe_checkout_orders
Feature: Canceling an order with Stripe Checkout
    In order to cancel a not paid order
    As an Administrator
    I want to be able to cancel a Stripe not paid order

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "Green Arrow"
        And the store ships everywhere for free
        And the store has a payment method "Stripe" with a code "stripe" and Stripe Checkout payment gateway without using authorize
        And there is a customer "oliver@teamarrow.com" that placed an order "#00000022"
        And the customer bought a single "Green Arrow"
        And the customer chose "Free" shipping method to "United States" with "Stripe" payment
        And this order is not yet paid using Stripe Checkout
        And I am logged in as an administrator

    @ui @api
    Scenario: Cancelling the order when a checkout session is still available
        Given I am viewing the summary of this order
        And I am prepared to expire this order
        When I cancel this order
        Then I should be notified that it has been successfully updated
        And it should have payment with state cancelled
        And it should have payment state "Cancelled"

    @ui @api
    Scenario: Cancelling the order after the customer canceled the payment
        Given I am viewing the summary of this order
        And I am prepared to expire this order
        And this order payment has been canceled
        And I am prepared to expire this order
        When I cancel this order
        Then I should be notified that it has been successfully updated
        And it should have payment with state cancelled
        And it should have payment state "Cancelled"
