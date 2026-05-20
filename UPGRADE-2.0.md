# Upgrade from 1.0 to 2.0

## Stripe PHP SDK upgrade

`stripe/stripe-php` is bumped from `^16.1` to `^20.0`. Crossing those majors brings breaking changes — both at this 
plugin's public surface (DI parameters, service decorators, providers) and transitively through the Stripe SDK / Stripe 
API itself.

The subsections below list only the changes that can affect **end applications** embedding this plugin.

### Stripe PHP SDK requirement

`composer.json` now requires `stripe/stripe-php: ^20.0` (was `^16.1`). Applications depending on the SDK directly inherit
the bump and should review the upstream changelog for BC breaks introduced across v17–v20:

https://github.com/stripe/stripe-php/blob/master/CHANGELOG.md

### Stripe API version (default)

The plugin does not call `Stripe::setApiVersion()`, so it inherits the default pinned by the SDK. Bumping the SDK across
four majors moves the default forward to a newer Basil API. As a result:

- Live webhook payloads will use the new schema. Custom processors tagged
  `flux_se.sylius_stripe.processor.webhook_event.{checkout,web_elements}` should be reviewed.
- Existing webhook endpoints in your Stripe Dashboard keep delivering events on the API version they were created with —
  only newly created (or explicitly upgraded) endpoints will match the new SDK default. Verify both sides agree before
  going live.

To lock to a specific version, call `Stripe::setApiVersion()` from your application bootstrap.

### `Invoice.payment_intent` moved under `Invoice.payments`

Stripe removed the direct `Invoice.payment_intent` field. The PaymentIntent attached to a subscription invoice now lives
under `Invoice.payments.data[].payment.payment_intent`, gated by `payment.type === 'payment_intent'`.

You must migrate if your application:

- decorates / extends `SubscriptionModeTransitionProvider` or `RefundSubscriptionInitProvider`,
- reads `payment.details['invoice']['payment_intent']` directly anywhere (listeners, controllers, fixtures, reports).

#### Before

```php
$paymentIntentId = $invoice['payment_intent']; // string|array
```

#### After

```php
$paymentIntentId = null;
foreach ($invoice['payments']['data'] ?? [] as $invoicePayment) {
    $payment = $invoicePayment['payment'] ?? null;
    if (null === $payment || 'payment_intent' !== ($payment['type'] ?? null)) {
        continue;
    }

    $pi = $payment['payment_intent'] ?? null;
    $paymentIntentId = is_array($pi) ? ($pi['id'] ?? null) : $pi;
    break;
}
```

See `src/Provider/Transition/Checkout/SubscriptionModeTransitionProvider.php` for a typed-SDK reference.

### `flux_se.sylius_stripe.checkout.retrieve.expand_fields` parameter changed

Defined in `config/services/providers/checkout/retrieve_params_providers.yaml`.

- **Removed:** `invoice.charge`, `invoice.payment_intent`, `invoice.payment_intent.latest_charge`,
  `invoice.payment_intent.payment_method`
- **Added:** `invoice.payments`

If you override this parameter, rebuild your list around `invoice.payments`. The deeper
`invoice.payments.data.payment.payment_intent.*` chain is intentionally **not** in `expand_fields` — Stripe caps
`expand[]` at 4 levels, and the PaymentIntent is fetched separately by the new manager (see below).

### `flux_se.sylius_stripe.web_elements.retrieve.expand_fields` parameter changed

`payment_method` was added (alongside the existing `latest_charge`). If you override this parameter, add
`payment_method` back — it is required for subscription-mode PaymentIntent enrichment to work end-to-end.

## Express Checkout (cart page)

2.0 introduces Express Checkout (ECE) on the cart page — a single button rendering Apple Pay, Google Pay, Link
and whatever other wallets your Stripe Dashboard exposes. The feature is opt-in per PaymentMethod (the
`enable_express_checkout` toggle in the gateway configuration), but the steps below apply even to applications
that do **not** plan to enable it, because the new routes and the rewired command provider land regardless.

### Shop routes must be imported

The plugin now ships an additional shop routes file that the bundle does **not** auto-load. Add it to your
application's route configuration:

```yaml
# config/routes/flux_se_sylius_stripe.yaml

flux_se_sylius_stripe_express_checkout_shop:
    resource: "@FluxSESyliusStripePlugin/config/routes/shop_express_checkout.yaml"
```

The file `config/routes/shop_express_checkout.yaml` registers three endpoints under `/express-checkout/`:

| Route name | Method | Path |
|---|---|---|
| `flux_se_sylius_stripe_express_checkout_configuration` | `GET` | `/express-checkout/configuration` |
| `flux_se_sylius_stripe_express_checkout_shipping_rates` | `POST` | `/express-checkout/shipping-rates` |
| `flux_se_sylius_stripe_express_checkout_confirm` | `POST` | `/express-checkout/confirm` |

Without the import, `GET /express-checkout/configuration` returns 404, the cart-page JavaScript silently
hides itself, and the wallet button never appears — there is no visible error.

If your application uses a non-default firewall pattern (e.g. `^/(en|fr)/`), make sure these paths fall inside
the shop firewall — they rely on the cart session like the rest of the shop area.

### New required Stripe webhook events when Express Checkout is enabled

Express Checkout always creates a Stripe `PaymentIntent`, regardless of the gateway type backing the
PaymentMethod. This means a `stripe_checkout` PaymentMethod with `enable_express_checkout` turned on must
subscribe to PaymentIntent events **in addition** to the existing `checkout.session.*` ones.

Add the following events to the Stripe webhook endpoint of every `stripe_checkout` PaymentMethod that has the
toggle on:

- `payment_intent.succeeded`
- `payment_intent.canceled`
- `payment_intent.processing`

For `stripe_web_elements` PaymentMethods the same three events are already required by the regular flow, so
the toggle does not change that list.

Without these events the ECE payment never receives a completion signal — the Sylius `Payment` stays in the
`processing` state and never transitions to `completed`. The full per-gateway matrix is in
`docs/EXPRESS-CHECKOUT.md` (section *Webhook events*).

### `flux_se.sylius_stripe.command_provider.checkout` now wraps the old class

The service ID `flux_se.sylius_stripe.command_provider.checkout` points to a **different class** in 2.0. The
old class is still wired, but under a new ID.

#### Before (1.x)

```yaml
flux_se.sylius_stripe.command_provider.checkout:
    class: Sylius\Bundle\PaymentBundle\CommandProvider\ActionsCommandProvider
```

#### After (2.0)

```yaml
flux_se.sylius_stripe.command_provider.checkout.actions:
    class: Sylius\Bundle\PaymentBundle\CommandProvider\ActionsCommandProvider
    # ...

flux_se.sylius_stripe.command_provider.checkout:
    class: FluxSE\SyliusStripePlugin\CommandProvider\Checkout\CheckoutOrPaymentIntentCommandProvider
    arguments:
        - '@flux_se.sylius_stripe.command_provider.checkout.actions'
        - '@flux_se.sylius_stripe.command_provider.web_elements'
```

The new wrapper inspects the Stripe object stored on the `PaymentRequest` (Checkout `Session` vs
`PaymentIntent`) and delegates to the Web Elements command provider whenever it sees a PaymentIntent. This is
what lets a `payment_intent.*` webhook arriving on a `stripe_checkout` PaymentMethod URL (the ECE case above)
reach the correct command pipeline.

This is a **real BC change** for anyone decorating that service:

- If you decorated `flux_se.sylius_stripe.command_provider.checkout` to extend `ActionsCommandProvider`
  (e.g. to register an extra `action`), move the decoration to
  `flux_se.sylius_stripe.command_provider.checkout.actions`.
- If you decorated it to intercept the dispatch itself, keep the same ID but note that `'$.inner'` is now
  `CheckoutOrPaymentIntentCommandProvider`, not `ActionsCommandProvider`. The wrapper's constructor signature
  is `(PaymentRequestCommandProviderInterface $checkoutCommandProvider, PaymentRequestCommandProviderInterface $webElementsCommandProvider)`.

## `payment_method_types` gateway configuration removed

The `payment_method_types` field in the Stripe gateway configuration (deprecated in 1.1 — see `UPGRADE-1.1.md`) is fully 
removed in 2.0. Stripe's Payment Element / Checkout Session now relies on the automatic payment methods configured 
in your Stripe Dashboard for every customer.

**Removed surface:**

- Form field `payment_method_types` in `FluxSE\SyliusStripePlugin\Form\Type\StripeGatewayConfigurationType`
- Class `FluxSE\SyliusStripePlugin\Provider\PaymentMethodTypesProvider`
- Services `flux_se.sylius_stripe.provider.checkout.create.payment_method_types` and
  `flux_se.sylius_stripe.provider.web_elements.create.payment_method_types`
- Admin templates under `@FluxSESyliusStripePlugin/admin/payment_method/form/payment_method_types*` and the
  associated twig hooks
- Translation keys `flux_se_sylius_stripe_plugin.form.gateway_configuration.stripe.payment_method_types`,
  `...info.payment_method_types_deprecated`, `...action.manage_payment_methods`

**Migration:**

1. Before upgrading, follow the migration steps in `UPGRADE-1.1.md` — open your Stripe Dashboard → Settings →
   Payment methods, enable the methods you want to offer, and clear the "Payment method types" field in each
   Sylius Stripe payment method.
2. After upgrading, the field disappears from the admin form. Any remaining `payment_method_types: [...]` value
   still serialized inside `payment_method.gateway_config.config` is silently ignored — no data migration is provided.
   If you want a clean payload, clear it manually (e.g. via a one-off update query) before or after the upgrade;
   it has no functional impact.

**For developers extending the plugin:** if your custom code references the removed class, services,
templates or twig hooks, remove those references before upgrading.

## Restricted API Key required for the `secret_key` field

The `Restricted API key` field of the Stripe gateway configuration now only accepts a Restricted API Key (`rk_test_…` / `rk_live_…`) 
generated by the [Sylius Stripe App][link-sylius-stripe-app]. Standard Stripe secret keys (`sk_test_…` / `sk_live_…`), 
which 1.1 accepted with a runtime deprecation notice (`UPGRADE-1.1.md`), are rejected by the form validator in 2.0.

**Removed surface:**

- The `sk_` branch of `SECRET_KEY_PATTERN` in `FluxSE\SyliusStripePlugin\Form\Type\StripeGatewayConfigurationType` 
  (regex is now `/^rk_(test|live)_/`).
- Admin info-box "legacy" copy and the secondary CTA to the Stripe Dashboard.
- Translation keys `info.secret_key_recommended_title`, `info.secret_key_recommended_body`, `info.secret_key_legacy_body`, 
  `action.secret_key`.

**Backwards-compatibility kept on purpose:**

- `Stripe/Factory/ClientFactory::createFromPaymentMethod` still builds a working `StripeClient` from a `sk_*` value 
  persisted before the upgrade. A `trigger_deprecation` notice is emitted on every build so the issue surfaces in logs. 
  The class is **not** the right place to fail-fast: webhook delivery and refund flows would otherwise break
  for anyone who upgrades before migrating their keys.
- The admin form keeps a dynamic `alert-warning` rendered under the field whenever the saved `secret_key` starts 
  with `sk_`, telling the admin that saving will fail until the key is replaced. Migrate, then the warning disappears.

The internal gateway_config key is still `secret_key` (no data migration is needed for existing payment methods that 
already store an `rk_*` value there).

**Migration:**

1. Install the [Sylius Stripe App][link-sylius-stripe-app] on your Stripe account.
2. Open the App's Settings Page and copy the generated Restricted API Key (`rk_test_…` / `rk_live_…`).
3. In Sylius admin, edit every Stripe payment method whose secret key still starts with `sk_` and paste the `rk_*` key 
   into the `Restricted API key` field. Save.

Payment methods that already held an `rk_*` value before upgrading need no further action.

**For developers extending the plugin:** if you imported the `SECRET_KEY_PATTERN` constant directly, the value changed 
(the name did not). Any custom validator allowing `sk_*` will start drifting from the plugin's behaviour after upgrading.

[link-sylius-stripe-app]: https://marketplace.stripe.com/apps/install/link/com.sylius.stripe
