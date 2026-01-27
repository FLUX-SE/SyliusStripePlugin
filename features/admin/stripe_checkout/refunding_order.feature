@managing_stripe_checkout_orders
Feature: Refunding an order with Stripe Checkout Session
    In order to return the money to the Customer
    As an Administrator
    I want to be able to refund a Stripe paid order

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "Green Arrow"
        And the store ships everywhere for free
        And the store has a payment method "Stripe" with a code "stripe" and Stripe Checkout payment gateway without using authorize
        And there is a customer "oliver@teamarrow.com" that placed an order "#00000001"
        And the customer bought a single "Green Arrow"
        And the customer chose "Free" shipping method to "United States" with "Stripe" payment
        And I am logged in as an administrator

    @ui @api @without_sylius_refund_plugin
    Scenario: Initializing the Stripe refund for a Stripe Checkout Session mode payment
        Given this order is already paid using Stripe Checkout
        And I am viewing the summary of this order
        And I am prepared to refund this order
        When I mark this order's payment as refunded
        Then I should be notified that the order's payment has been successfully refunded
        And it should have payment with state refunded
        And it should have payment state "Refunded"

    @ui @api @without_sylius_refund_plugin
    Scenario: Initializing the Stripe refund for a Stripe Checkout Session mode subscription
        Given this order related to a subscription is already paid using Stripe Checkout
        And I am viewing the summary of this order
        And I am prepared to refund this order related to a subscription
        When I mark this order's payment as refunded
        Then I should be notified that the order's payment has been successfully refunded
        And it should have payment with state refunded
        And it should have payment state "Refunded"
