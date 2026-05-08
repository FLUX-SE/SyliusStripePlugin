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
