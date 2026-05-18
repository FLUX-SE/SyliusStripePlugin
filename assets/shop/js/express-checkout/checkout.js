import { initExpressCheckout } from './core';

const SELECTOR = '[data-sylius-stripe-express-checkout-checkout]';

document.querySelectorAll(SELECTOR).forEach((container) => {
    initExpressCheckout(container);
});
