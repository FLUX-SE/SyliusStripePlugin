# Express Checkout

Stripe's [Express Checkout Element](https://docs.stripe.com/elements/express-checkout-element)
exposes wallet buttons (Apple Pay, Google Pay, Link, PayPal, Amazon Pay) that let a
customer pay directly from the cart page or from the checkout sidebar on any step,
skipping the rest of the regular multi-step Sylius checkout.

**The plugin does not hard-code which wallets to show.** The buttons rendered depend on:

- which wallets are enabled in the merchant's **Stripe Dashboard** (Settings → Payment
  methods),
- whether the cart-page domain is **registered for that wallet** in Stripe Dashboard
  (Settings → Payment method domains), and
- whether the customer's browser / device supports the wallet (Google Pay needs Chrome
  with a saved card; Apple Pay needs Safari on macOS/iOS; Link recognises a returning
  customer by email or cookie).

Stripe's Express Checkout Element normalizes the wallet-specific payloads into a single
shape, so the plugin's backend (`ConfirmAction`, address normalizer) handles every
supported wallet through the same code path.

## How it works

Independently of which Stripe gateway PaymentMethod the merchant uses, the Express Checkout
Element always runs on the PaymentIntent stack (`stripe.elements()` + `stripe.confirmPayment`).
The plugin uses the existing Web Elements `CapturePaymentRequestHandler` to create the
PaymentIntent — even when the resolved PaymentMethod is configured as `stripe_checkout`
(the publishable / secret keys are per Stripe account, not per gateway type).

The same backend flow runs no matter which placement (cart or checkout sidebar) the
customer clicks the wallet button from.

```
┌──────────────────────┐         ┌────────────────────────────┐
│ Cart or checkout     │         │ Sylius backend             │
│ (Express Checkout JS)│         │                            │
│                      │ ───────▶│ GET  /express-checkout/    │
│                      │ config  │      configuration         │
│                      │◀─────── │ (publishableKey, amount…)  │
│                      │         │                            │
│                      │ shipping│                            │
│                      │ change  │ POST /express-checkout/    │
│                      │ ───────▶│      shipping-rates        │
│                      │◀─────── │ (shipping rates per Sylius │
│                      │ rates   │  shipping methods resolver)│
│                      │         │                            │
│                      │ confirm │                            │
│                      │ ───────▶│ POST /express-checkout/    │
│                      │         │      confirm               │
│                      │         │ → state machine: address → │
│                      │         │   select_shipping → select │
│                      │         │   _payment → complete      │
│                      │         │ → dispatch                 │
│                      │         │   WebElements\Capture cmd  │
│                      │         │ → Stripe PaymentIntent     │
│                      │◀─────── │ (clientSecret, returnUrl)  │
│                      │         │                            │
│ stripe.confirmPayment│         │                            │
│ → 3DS if needed      │         │                            │
│ → redirect returnUrl │         │                            │
└──────────────────────┘         └────────────────────────────┘
                                              ▲
                                              │ webhook
                                              │ payment_intent.succeeded
                                  ┌───────────────────────────┐
                                  │ Sylius webhook handler    │
                                  │ → Payment to `completed`  │
                                  └───────────────────────────┘
```

## Enabling on a PaymentMethod

1. Open the Sylius admin and edit a payment method configured with either the
   **Stripe (Checkout)** or **Stripe (Web Elements)** gateway.
2. Check **Enable Express Checkout (Google Pay / Apple Pay)** in the gateway configuration form.
3. Save.

When multiple PaymentMethods on the same channel have the toggle on, the plugin prefers
the `stripe_web_elements` one (its webhook subscriptions already include the
`payment_intent.*` events).

> 📖 Express Checkout button only appears if the channel has at least one enabled
> PaymentMethod with the toggle on. Otherwise every placement (cart and checkout
> sidebar) is hidden silently — the `GET /express-checkout/configuration` endpoint
> returns 204 and the JS no-ops.

## Placements

Out of the box the plugin renders the wallet button in four places, each backed by its
own Sylius Twig Hook entry:

| Placement              | Hook                                                            | Entry name         |
|------------------------|-----------------------------------------------------------------|--------------------|
| Cart page              | `sylius_shop.cart.index.content.form.sections.general#right`    | `express_checkout` |
| Checkout — address     | `sylius_shop.checkout.address.sidebar.summary`                  | `express_checkout` |
| Checkout — shipping    | `sylius_shop.checkout.select_shipping.sidebar.summary`          | `express_checkout` |
| Checkout — payment     | `sylius_shop.checkout.select_payment.sidebar.summary`           | `express_checkout` |

The `complete` step has no sidebar (Sylius's `complete.html.twig` empties the `sidebar`
block), so the button is not rendered there — no override is needed to hide it.

End apps disable any placement by adding an `enabled: false` override in their own
`config/packages/sylius_twig_hooks.yaml`. Disable the cart placement:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_shop.cart.index.content.form.sections.general#right':
            express_checkout:
                enabled: false
```

Disable the button on the payment step only:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_shop.checkout.select_payment.sidebar.summary':
            express_checkout:
                enabled: false
```

To hide the button on all checkout steps at once, override all three checkout hooks
with `enabled: false`.

## Stripe Dashboard configuration

### Payment method domain registration

The Express Checkout Element only renders on **registered domains**. Add every public host
where the shop runs:

Stripe Dashboard → Settings → Payment method domains → **Add a new domain**.

For local development with `https://127.0.0.1:8009/` add `127.0.0.1` as the domain too.

When a domain is added through Payment method domains, Stripe automatically handles
Apple Pay's domain association verification — no `apple-developer-merchantid-domain-association`
file needs to be hosted manually. The Dashboard UI shows a green check next to each wallet
once verification succeeds; if it fails, follow the on-screen remediation steps (typically a
DNS or HTTPS issue).

### Activate wallets

Stripe Dashboard → Settings → Payment methods → enable each wallet you want to expose:

- **Apple Pay** — supported in Safari on macOS / iOS; requires the domain to be verified
  via Payment method domains (see above).
- **Google Pay** — supported in Chrome (and Chromium-based browsers) when the customer
  has a saved card in their Google account.
- **Link** — Stripe's cross-merchant wallet, recognises returning customers by email or
  cookie; no extra setup beyond enabling it in the Dashboard.
- **PayPal / Amazon Pay** — available in some regions / accounts; same Dashboard toggle.

The plugin defers to `'auto'` for every wallet, so the choice of which buttons appear is
fully controlled from the Stripe Dashboard.

### Webhook events

The events the webhook endpoint must subscribe to depend on the gateway type and on
whether Express Checkout is enabled on that PaymentMethod.

| PaymentMethod gateway | Express Checkout enabled | Required webhook events |
|---|---|---|
| `stripe_web_elements` | yes | `payment_intent.succeeded`, `payment_intent.canceled`, `payment_intent.processing` (already required by Web Elements) |
| `stripe_checkout` | no | `checkout.session.completed`, `checkout.session.expired`, `checkout.session.async_payment_failed`, `checkout.session.async_payment_succeeded` |
| `stripe_checkout` | **yes** | the four `checkout.session.*` above **and** the three `payment_intent.*` from the Web Elements row |

The Express Checkout flow always creates a PaymentIntent (never a Checkout Session), so a
`stripe_checkout` PaymentMethod with the toggle on needs the `payment_intent.*` events
added to its existing webhook endpoint.

## Local testing

### Prerequisites

- HTTPS — Express Checkout never renders on plain HTTP. The Symfony server already
  provides HTTPS via a self-signed certificate (`https://127.0.0.1:8009/`).
- A registered payment method domain (see above).
- A test webhook signing secret obtained from `stripe listen` and pasted into the
  PaymentMethod's `webhook_secret_keys` field in the Sylius admin.
- Chrome (or another Chromium browser) with a Google Pay sandbox card configured at
  `chrome://settings/payments`. Other browsers will hide the Google Pay button even
  when the rest of the configuration is correct.

### Stripe CLI forward

Replace `MY_PAYMENT_METHOD_CODE` with the **code** of the PaymentMethod in the Sylius admin.

Web Elements PaymentMethod with Express Checkout on:

```shell
stripe listen \
    --events payment_intent.succeeded,payment_intent.canceled,payment_intent.processing \
    --forward-to https://127.0.0.1:8009/payment-methods/MY_PAYMENT_METHOD_CODE
```

Checkout PaymentMethod with Express Checkout on:

```shell
stripe listen \
    --events checkout.session.completed,checkout.session.async_payment_failed,checkout.session.async_payment_succeeded,checkout.session.expired,payment_intent.succeeded,payment_intent.canceled,payment_intent.processing \
    --forward-to https://127.0.0.1:8009/payment-methods/MY_PAYMENT_METHOD_CODE
```

Copy the `whsec_…` value printed by `stripe listen` into the PaymentMethod's webhook
secret keys and keep the process running.

### Manual smoke test flow

1. Add a product to the cart.
2. Open `https://127.0.0.1:8009/` and navigate to the cart page.
3. The Google Pay button should appear above the cart summary.
4. Click Google Pay → choose a shipping address in the wallet popup → the popup shows
   shipping methods returned by Sylius's `ShippingMethodsResolver` (filtered by channel
   zones).
5. Pick a rate → confirm with Google Pay.
6. The browser is redirected to Sylius's `sylius_shop_order_after_pay` route after
   `stripe.confirmPayment` completes (with 3DS if the test card requires it).
7. The Stripe CLI process should log `payment_intent.succeeded` being forwarded to the
   plugin webhook endpoint and the Sylius `Payment` should transition to `completed` in
   the admin.

### Troubleshooting

| Symptom | Likely cause |
|---|---|
| Cart page renders no wallet button | `GET /express-checkout/configuration` returns 204 — check the toggle is on, channel matches, cart is not empty, and the channel has an enabled shipping method |
| 4xx in the browser network tab on `/configuration` | Routes not imported in the application — see step 4 of [Installation](../README.md#installation) |
| Wallet popup opens but reports "no shipping option" | The address (country / region) is not covered by any enabled `ShippingMethod`. Add a zone covering the country in the Sylius admin |
| `stripe.confirmPayment` throws "no such payment_intent" | The `confirm` endpoint returned an error and the JS did not stop — open the network tab and inspect the response body |
| Webhook delivers but `Payment` stays `processing` | For a `stripe_checkout` PaymentMethod, check the `payment_intent.*` events are subscribed (see the table above) |

## CSRF protection

The two state-changing endpoints (`POST /express-checkout/shipping-rates` and `POST /express-checkout/confirm`) 
require a CSRF token under the id `sylius_stripe_express_checkout` sent in the `X-CSRF-Token` request header.
The plugin's default Twig partial renders the token into a `data-csrf-token` attribute on the container and the bundled 
JS forwards it on every POST — no integrator action is needed when reusing the shipped templates.

Integrators who **override** `@FluxSESyliusStripePlugin/shop/express_checkout/_button.html.twig` must keep the
`data-csrf-token="{{ csrf_token('sylius_stripe_express_checkout') }}"` attribute. Integrators who ship their own ECE 
frontend must send the token in `X-CSRF-Token` themselves. Applications that disable `framework.csrf_protection`
in `config/packages/framework.yaml` will receive `403 Forbidden` from these endpoints.

## Architecture notes

- The Express Checkout pipeline reuses `FluxSE\SyliusStripePlugin\CommandHandler\WebElements\CapturePaymentRequestHandler`
  unconditionally. The `ConfirmAction` controller dispatches
  `Command\WebElements\CapturePaymentRequest` directly on the payment-request command
  bus instead of going through `PaymentRequestAnnouncer` — the announcer would map a
  `stripe_checkout` PaymentMethod to the Checkout Session command, which is incompatible
  with `stripe.confirmPayment`.
- Webhook routing for a `stripe_checkout` PaymentMethod with Express Checkout on relies
  on a cross-tag in `config/services/processors.yaml`: the Web Elements `payment_intent`
  processor is tagged with both `..web_elements` and `..checkout` so it is iterated by
  the checkout composite as well. The transition processor in the checkout notify
  handler is a composite that dispatches on `Payment.details['object']` —
  `checkout.session` to the session processor, `payment_intent` to the PaymentIntent
  processor.
- The wallet partial is mounted at multiple Twig Hook anchors — see the [Placements](#placements)
  section. The shared `_button.html.twig` carries a `placement` context (`cart` or
  `checkout`) and the JS bootstraps (`assets/shop/js/express-checkout/cart.js` and
  `…/checkout.js`) each scan for the matching `data-sylius-stripe-express-checkout-<placement>`
  attribute.
- The JS module loads Stripe.js v3 lazily from the CDN (sharing the script tag with the
  existing order-pay flow if both are present).
- The `expressCheckout` Element is created with an empty `paymentMethods` config —
  every wallet defaults to `'auto'`, so the visible buttons are driven entirely by the
  merchant's Stripe Dashboard settings and the customer's browser support.
