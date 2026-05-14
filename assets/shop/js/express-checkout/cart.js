// Mounts Stripe's Express Checkout Element next to the cart summary and wires it to
// the plugin's AJAX endpoints (configuration / shipping-rates / confirm).
//
// The Express Checkout Element is part of Stripe Elements. It always runs on the
// PaymentIntent stack — regardless of whether the merchant's enabled PaymentMethod
// is stripe_web_elements or stripe_checkout (the ConfirmAction forces the WE pipeline).

(function () {
    'use strict';

    const SELECTOR = '[data-sylius-stripe-express-checkout]';
    const STRIPE_JS_URL = 'https://js.stripe.com/v3/';

    function loadStripeJs() {
        return new Promise((resolve, reject) => {
            if (typeof window.Stripe !== 'undefined') {
                resolve(window.Stripe);

                return;
            }

            const existing = document.querySelector(`script[src="${STRIPE_JS_URL}"]`);
            if (existing) {
                existing.addEventListener('load', () => resolve(window.Stripe));
                existing.addEventListener('error', reject);

                return;
            }

            const script = document.createElement('script');
            script.src = STRIPE_JS_URL;
            script.onload = () => resolve(window.Stripe);
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    async function fetchJson(url) {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (response.status === 204 || !response.ok) {
            return null;
        }

        return response.json();
    }

    async function postJson(url, body, csrfToken) {
        const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }
        const response = await fetch(url, {
            method: 'POST',
            headers,
            body: JSON.stringify(body),
        });
        if (!response.ok) {
            return { error: `HTTP ${response.status}` };
        }

        return response.json();
    }

    function showError(container, message) {
        const target = container.querySelector('[data-sylius-stripe-express-checkout-errors]');
        if (target) {
            target.textContent = message;
            target.hidden = false;
        }
    }

    async function initContainer(container) {
        const configurationUrl = container.dataset.configurationUrl;
        const shippingRatesUrl = container.dataset.shippingRatesUrl;
        const confirmUrl = container.dataset.confirmUrl;
        const csrfToken = container.dataset.csrfToken;
        const mountPoint = container.querySelector('[data-sylius-stripe-express-checkout-mount]');

        if (!configurationUrl || !shippingRatesUrl || !confirmUrl || !mountPoint) {
            return;
        }

        const configuration = await fetchJson(configurationUrl);
        if (!configuration) {
            // 204 from server — feature disabled or pre-conditions not met. Keep cart layout intact.
            container.hidden = true;

            return;
        }

        let StripeFactory;
        try {
            StripeFactory = await loadStripeJs();
        } catch (e) {
            container.hidden = true;

            return;
        }

        if (typeof StripeFactory !== 'function') {
            container.hidden = true;

            return;
        }

        const stripe = StripeFactory(configuration.publishableKey);
        const elements = stripe.elements({
            mode: 'payment',
            amount: configuration.amount,
            currency: (configuration.currency || 'usd').toLowerCase(),
        });

        // Defer wallet selection to Stripe (all paymentMethods default to 'auto'):
        // the buttons rendered depend on the wallets enabled in the merchant's Stripe
        // Dashboard and on what the customer's browser supports.
        //
        // Force a single-column layout — the cart right sidebar is narrow and the default
        // 2-column wrap truncates long localized labels (e.g. Klarna in PL).
        // `overflow: 'never'` disables the "more options" affordance so every wallet is
        // rendered as a full-width button instead of being hidden behind a chevron.
        // See https://docs.stripe.com/elements/express-checkout-element
        const expressCheckout = elements.create('expressCheckout', {
            buttonHeight: 48,
            layout: {
                maxColumns: 1,
                maxRows: 0,
                overflow: 'never',
            },
        });
        expressCheckout.mount(mountPoint);

        expressCheckout.on('ready', (event) => {
            if (!event.availablePaymentMethods) {
                container.hidden = true;
            }
        });

        // Tell the wallet popup what we want from the customer. Without this Stripe
        // defaults to "no shipping address, no email" and the confirm payload arrives
        // without the customer email — backend ConfirmAction then returns 422.
        // A placeholder shipping rate is mandatory; real rates arrive via the
        // shippingaddresschange event once the wallet shares the address.
        expressCheckout.on('click', (event) => {
            event.resolve({
                emailRequired: true,
                phoneNumberRequired: false,
                shippingAddressRequired: Boolean(configuration.shippingRequired),
                allowedShippingCountries: configuration.allowedCountryCodes || [],
                business: { name: configuration.merchantName || 'Shop' },
                shippingRates: configuration.shippingRequired
                    ? [{
                        id: 'placeholder',
                        displayName: 'Calculating shipping…',
                        amount: 0,
                    }]
                    : undefined,
            });
        });

        expressCheckout.on('shippingaddresschange', async (event) => {
            const result = await postJson(shippingRatesUrl, { address: event.address }, csrfToken);
            if (!result || result.error || !Array.isArray(result.shippingRates) || result.shippingRates.length === 0) {
                event.reject();

                return;
            }
            event.resolve({
                shippingRates: result.shippingRates,
                lineItems: result.lineItems,
            });
        });

        expressCheckout.on('shippingratechange', async (event) => {
            const result = await postJson(shippingRatesUrl, {
                address: event.address,
                shippingRateId: event.shippingRate.id,
            }, csrfToken);
            event.resolve({ lineItems: (result && result.lineItems) || [] });
        });

        expressCheckout.on('confirm', async (event) => {
            const result = await postJson(confirmUrl, {
                expressPaymentType: event.expressPaymentType,
                shippingAddress: event.shippingAddress,
                billingDetails: event.billingDetails,
                shippingRate: event.shippingRate,
            }, csrfToken);

            if (!result || result.error || !result.clientSecret) {
                event.paymentFailed({ reason: 'fail' });
                showError(container, (result && result.error) || 'Express Checkout failed');

                return;
            }

            const { error } = await stripe.confirmPayment({
                elements,
                clientSecret: result.clientSecret,
                confirmParams: { return_url: result.returnUrl },
            });

            if (error) {
                showError(container, error.message);
            }
        });
    }

    function bootstrap() {
        document.querySelectorAll(SELECTOR).forEach((container) => {
            initContainer(container);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }
})();
