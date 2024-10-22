# SyliusStripePlugin

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-total-downloads]][link-total-downloads]  
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]


This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

1. Run `composer require flux-se/sylius-stripe-plugin`.

2. Import routes
    ```yaml
    # config/routes/sylius_shop.yaml

    flux_se_sylius_stripe_shop:
        resource: "@FluxSESyliusStripePlugin/config/shop_routing.yaml"
        prefix: /{_locale}
        requirements:
            _locale: ^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$

    # config/routes/sylius_admin.yaml

    flux_se_sylius_stripe_admin:
        resource: "@FluxSESyliusStripePlugin/config/admin_routing.yml"
        prefix: /admin
    ```

3. Import configuration
    ```yaml
    # config/packages/_sylius.yaml

    imports:
    # ...
    - { resource: "@FluxSESyliusStripePlugin/config/config.yaml" }
    ```

4. Apply migrations
    ```bash
    bin/console doctrine:migrations:migrate
    ```

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
