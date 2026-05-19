import { initExpressCheckout } from './core';

const SELECTOR = '[data-sylius-stripe-express-checkout-cart]';

document.querySelectorAll(SELECTOR).forEach((container) => {
    initExpressCheckout(container);
});
