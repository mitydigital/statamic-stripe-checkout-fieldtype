import StripeCheckout from './fieldtypes/StripeCheckout.vue';

Statamic.booting(() => {
    Statamic.$components.register('stripe_checkout-fieldtype', StripeCheckout);
});
