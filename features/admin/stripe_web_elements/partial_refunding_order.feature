@managing_stripe_web_elements_orders_refund @with_sylius_refund_plugin
Feature: Partially refunding an order with Stripe Web Elements
    In order to return part of the money to the Customer
    As an Administrator
    I want to be able to partially refund a Stripe paid order

    Background:
        Given the store operates on a single green channel in "United States"
        And the store has a product "Green Arrow" priced at "$10.00"
        And the store has "Galaxy Post" shipping method with "$10.00" fee
        And the store has a payment method "Stripe" with a code "stripe" and Stripe Web Elements payment gateway without using authorize
        And there is a customer "oliver@teamarrow.com" that placed an order "#00000001"
        And the customer bought 2 "Green Arrow" products
        And the customer chose "Galaxy Post" shipping method to "United States" with "Stripe" payment
        And this order is already paid using Stripe web elements
        And I am logged in as an administrator

    @ui @javascript
    Scenario: Partially refunding a single order unit
        Given I am prepared to partially refund "$10.00" from this order
        When I want to refund some units of order "#00000001"
        And I decide to refund 1st "Green Arrow" product with "Stripe" payment
        Then I should be notified that selected order units have been successfully refunded
        And this order refunded total should be "$10.00"
        And I should not be able to refund 1st unit with product "Green Arrow"
        But I should still be able to refund 2nd unit with product "Green Arrow" with "Stripe" payment

    @ui @javascript
    Scenario: Partially refunding part of an order unit
        Given I am prepared to partially refund "$5.00" from this order
        When I want to refund some units of order "#00000001"
        And I decide to refund "$5.00" from 1st "Green Arrow" product with "Stripe" payment
        Then I should be notified that selected order units have been successfully refunded
        And this order refunded total should be "$5.00"
        And 1st "Green Arrow" product should have "$5.00" refunded

    @ui @javascript
    Scenario: Partially refunding the order shipment
        Given I am prepared to partially refund "$5.00" from this order
        When I want to refund some units of order "#00000001"
        And I decide to refund "$5.00" from order shipment with "Stripe" payment
        Then I should be notified that selected order units have been successfully refunded
        And this order refunded total should be "$5.00"

    @ui @javascript
    Scenario: Partially refunding multiple order units
        Given I am prepared to partially refund "$20.00" from this order
        When I want to refund some units of order "#00000001"
        And I decide to refund 2 "Green Arrow" products with "Stripe" payment
        Then I should be notified that selected order units have been successfully refunded
        And this order refunded total should be "$20.00"

    @ui @javascript
    Scenario: Having order partially refunded when some items are refunded
        Given I am prepared to partially refund "$10.00" from this order
        When 1st "Green Arrow" product from order "#00000001" has already been refunded with "Stripe" payment
        Then this order's payment state should be "Partially refunded"

