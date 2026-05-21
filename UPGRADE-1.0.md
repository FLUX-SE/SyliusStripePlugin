# UPGRADE FROM 1.0.8 to 1.0.9

### `secret_key` Twig hook deprecated

The `secret_key` hook in the payment method gateway configuration form is deprecated and will be removed in 2.0.
Both API key fields are now rendered together by the `publishable_key` hook.
The default `secret_key` template renders nothing — the hook remains registered only to avoid hard breaks.

If you registered a custom template for the `secret_key` hook, move your customisation to `publishable_key`.

### Recommended: install the Sylius Stripe App for your API keys

The `Publishable key` and `Restricted API key (recommended) or secret key` fields still accept the values you can read from the Stripe 
Dashboard, but we recommend installing the [Sylius Stripe App][link-sylius-stripe-app] instead. Its Settings Page exposes
both keys you need:

- the publishable key (`pk_test_…` / `pk_live_…`) for the `Publishable key` field,
- a Restricted API Key (`rk_test_…` / `rk_live_…`) for the `Restricted API key (recommended) or secret key` field.

The App ships with the minimum scopes the plugin needs, and **standard secret keys (`sk_*`) will no longer be supported 
in plugin 2.0** — only Restricted API Keys (`rk_*`) will be accepted. Migrating now keeps you ahead of that change.

For Stripe's own rationale on why restricted keys exist and how they differ from standard secret keys, see [Stripe's documentation on restricted API keys][link-stripe-restricted-keys].

[link-sylius-stripe-app]: https://marketplace.stripe.com/apps/install/link/com.sylius.stripe
[link-stripe-restricted-keys]: https://docs.stripe.com/keys/restricted-api-keys
