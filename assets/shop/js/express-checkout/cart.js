// Cart-page Express Checkout (Google Pay / Apple Pay) integration.
//
// Mounts Stripe's Express Checkout Element next to the cart summary and wires it to
// the plugin's AJAX endpoints (configuration / shipping-rates / confirm).
//
// The Express Checkout Element is part of Stripe Elements. It always runs on the
// PaymentIntent stack — regardless of whether the merchant's enabled PaymentMethod
// is stripe_web_elements or stripe_checkout (the ConfirmAction forces the WE pipeline).

(function () {
    'use strict';

    const SELECTOR = '[data-flux-se-stripe-express-checkout]';
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

    async function postJson(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(body),
        });
        if (!response.ok) {
            return { error: `HTTP ${response.status}` };
        }

        return response.json();
    }

    function showError(container, message) {
        const target = container.querySelector('[data-flux-se-stripe-express-checkout-errors]');
        if (target) {
            target.textContent = message;
            target.hidden = false;
        }
    }

    async function initContainer(container) {
        const configurationUrl = container.dataset.configurationUrl;
        const shippingRatesUrl = container.dataset.shippingRatesUrl;
        const confirmUrl = container.dataset.confirmUrl;
        const mountPoint = container.querySelector('[data-flux-se-stripe-express-checkout-mount]');

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
        const expressCheckout = elements.create('expressCheckout', {});
        expressCheckout.mount(mountPoint);

        expressCheckout.on('ready', (event) => {
            if (!event.availablePaymentMethods) {
                container.hidden = true;
            }
        });

        expressCheckout.on('shippingaddresschange', async (event) => {
            const result = await postJson(shippingRatesUrl, { address: event.address });
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
            });
            event.resolve({ lineItems: (result && result.lineItems) || [] });
        });

        expressCheckout.on('confirm', async (event) => {
            const result = await postJson(confirmUrl, {
                expressPaymentType: event.expressPaymentType,
                shippingAddress: event.shippingAddress,
                billingDetails: event.billingDetails,
                shippingRate: event.shippingRate,
            });

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
