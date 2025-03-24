# SyliusStripePlugin

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-total-downloads]][link-total-downloads]  
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]


This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

1. Install the plugin ()
    ```shell
    composer require flux-se/sylius-stripe-plugin
    ```
2. Enable this plugin :
    ```php
    <?php
    
    # config/bundles.php
    
    return [
        // ...
        FluxSE\SyliusStripePlugin\FluxSESyliusStripePlugin::class => ['all' => true],
        // ...
    ];
    ```
3. Import configuration
    ```yaml
    # config/packages/flux_se_sylius_stripe.yaml

    imports:
    # ...
    - { resource: "@FluxSESyliusStripePlugin/config/config.yaml" }
    ```
## Configuration

 - Go to the admin area.
 - Log in.
 - Click on the left menu item "CONFIGURATION > Payment methods".
 - Create a new payment method type "Stripe (Checkout)" or "Stripe (Web Elements)":
   
   ![Create a new payment method][docs-assets-create-payment-method]
 - The next chapter will explain how to fill the payment method creation form.
 
### Payment Method configuration

A form will be displayed, fill-in the required fields :

#### 1. The "code" field (ex: "my_shop_stripe_checkout").

> 💡 The code will be the `gateway name`, it will be necessary to build the right webhook URL later
> (see [Webhook key](#webhook-key) section for more info).

#### 2. Choose which channels this payment method will be affected to.

#### 3. The gateway configuration ([need info from here](#api-keys)) :

![Gateway Configuration][docs-assets-gateway-configuration]

![Gateway Configuration][docs-assets-gateway-configuration-authorize]

> _📖 NOTE1: You can add as many webhook secret keys as you need here, however generic usage needs only one._

> _📖 NOTE2: the screenshot contains false test credentials._

#### 4. Give to this payment method a display name (and a description) for each language you need.

Finally, click on the "Create" button to save your new payment method.

### API keys

Get your `publishable_key` and your `secret_key` on your Stripe dashboard :

https://dashboard.stripe.com/test/apikeys

### Webhook key

Got to :

https://dashboard.stripe.com/test/webhooks

Then create a new endpoint with those events:

| Gateway | `stripe_checkout` | `stripe_web_elements` |
|-|-|-|
| Webhook events |  - `checkout.session.completed`<br> - `checkout.session.async_payment_failed`<br> - `checkout.session.async_payment_succeeded`<br> - `checkout.session.expired`<br> - `setup_intent.canceled` (⚠️ Only when using `setup` mode)<br> - `setup_intent.succeeded`  (⚠️ Only when using `setup` mode) |  - `payment_intent.canceled`<br> - `payment_intent.succeeded`<br> - `setup_intent.canceled` (⚠️ Only when using `setup` mode)<br> - `setup_intent.succeeded`  (⚠️ Only when using `setup` mode) |


The URL to fill is the route named `sylius_payment_method_notify` with the `{code}`
param equal to the `payment method code`, here is an example :

```
https://localhost/payment-methods/my_shop_stripe_checkout
```

> 📖 As you can see in this example the URL is dedicated to `localhost`, you will need to provide to
> Stripe a public host name to get the webhooks working.

> 📖 Use this command to know the exact structure of `sylius_payment_method_notify` route
>
> ```shell
> bin/console debug:router sylius_payment_method_notify
> ```

### Test or dev environment

Webhooks are triggered by Stripe on their server to your server.
If the server is into a private network, Stripe won't be allowed to reach your server.

Stripe provide an alternate way to catch those webhook events, you can use
`Stripe cli` : https://stripe.com/docs/stripe-cli
Follow the link and install `Stripe cli`, then use those command line to get
your webhook key :

First login to your Stripe account (needed every 90 days) :

```shell
stripe login
```

Then start to listen for the Stripe events (minimal ones are used here), forwarding request to your local server :

 1. Example with `my_shop_stripe_checkout` as payment method code:
    ```shell
    stripe listen \
       --events checkout.session.completed,checkout.session.async_payment_failed,checkout.session.async_payment_succeeded,checkout.session.expired \
       --forward-to https://localhost/payment/notify/unsafe/my_shop_stripe_checkout
    ```
 2. Example with `my_shop_stripe_web_elements` as payment method code:
    ```shell
    stripe listen \
       --events payment_intent.canceled,payment_intent.succeeded \
       --forward-to https://localhost/payment/notify/unsafe/my_shop_stripe_web_elements
    ```

> 💡 Replace --forward-to argument value with the right one you need.

When the command finishes, a webhook secret key is displayed, copy it to your
Payment method configuration edit form in the Sylius admin.

> ⚠️ Using the command `stripe trigger checkout.session.completed` will always result in a `500 error`,
> because the test object will not embed any usable metadata.

## Advance documentation

- [API (Sylius using APIPlatform)](docs/API.md)
- [Webhook events](docs/WEBHOOK-EVENTS.md)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [All Contributors](../../contributors)

## License

Please see the [License File](LICENSE.md) for more information about licensing.

[ico-version]: https://img.shields.io/packagist/v/flux-se/sylius-stripe-plugin.svg?style=flat-square
[ico-total-downloads]: https://img.shields.io/packagist/dt/flux-se/sylius-stripe-plugin.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-github-actions]: https://github.com/FLUX-SE/SyliusStripePlugin/workflows/Build/badge.svg

[link-packagist]: https://packagist.org/packages/flux-se/sylius-stripe-plugin
[link-total-downloads]: https://packagist.org/packages/flux-se/sylius-stripe-plugin
[link-github-actions]: https://github.com/FLUX-SE/SyliusStripePlugin/actions?query=workflow%3A"Build"
