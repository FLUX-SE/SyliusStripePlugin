# UPGRADE FROM 1.0.x to 1.1

## Gateway configuration

### `payment_method_types` is deprecated

Configuring `payment_method_types` in the Stripe gateway configuration (Sylius admin → Payment methods → 
Stripe (Checkout) / Stripe (Web Elements)) is deprecated and will be removed in 2.0.

When the field is non-empty, the plugin sends a static `payment_method_types` array to Stripe at PaymentIntent / Checkout 
Session creation, which overrides Stripe's automatic payment methods and prevents the Stripe Dashboard from controlling 
which methods are displayed to customers.

**Migration:**

1. Open your Stripe Dashboard → Settings → Payment methods and enable the methods you want to offer.
2. In Sylius admin, edit your Stripe payment method and clear all entries from the "Payment method types" field.
3. Save. Stripe will now dynamically present the most relevant subset of the configured methods based on the customer's 
   country, currency and transaction amount.

Existing installations that keep the field populated will continue to work in 1.1 with the legacy behaviour, 
but a deprecation notice will be triggered on every payment request. In 2.0 the field, the runtime provider 
and the related admin form section will be removed.

**For developers extending the plugin**, the following will be removed in Stripe Plugin 2.0:

- Class `FluxSE\SyliusStripePlugin\Provider\PaymentMethodTypesProvider`
- Service `flux_se.sylius_stripe.provider.checkout.create.payment_method_types`
- Service `flux_se.sylius_stripe.provider.web_elements.create.payment_method_types`

If your custom code references the class or either service (e.g. via DI decoration, alias or direct injection), 
remove those references before upgrading to 2.0.
