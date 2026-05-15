import { initExpressCheckout } from './core';

const SELECTOR = '[data-sylius-stripe-express-checkout-cart]';

function bootstrap() {
    document.querySelectorAll(SELECTOR).forEach((container) => {
        initExpressCheckout(container);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
} else {
    bootstrap();
}
