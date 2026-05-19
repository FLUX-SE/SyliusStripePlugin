# UPGRADE FROM 1.0.8 to 1.0.9

### `secret_key` Twig hook deprecated

The `secret_key` hook in the payment method gateway configuration form is deprecated and will be removed in 2.0.
Both API key fields are now rendered together by the `publishable_key` hook.
The default `secret_key` template renders nothing — the hook remains registered only to avoid hard breaks.

If you registered a custom template for the `secret_key` hook, move your customisation to `publishable_key`.
