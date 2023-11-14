<?php

use MityDigital\StatamicStripeCheckoutFieldtype\Facades\StripeCheckoutFieldtype;
use Statamic\Facades\Form;

it('gets the key from the config', function () {
    expect(StripeCheckoutFieldtype::getSecret())
        ->toBe('stripe_secret');
});

it('gets the cp currency from the config', function () {
    expect(StripeCheckoutFieldtype::getCpCurrency())
        ->toBe('AUD');
});

it('correctly checks if a form contains the stripe checkout fieldtype', function () {
    expect(StripeCheckoutFieldtype::doesFormHaveStripeCheckout(Form::find('has_stripe_checkout_fieldtype')))
        ->toBeTrue()
        ->and(StripeCheckoutFieldtype::doesFormHaveStripeCheckout(Form::find('no_stripe_checkout_fieldtype')))
        ->toBeFalse();
});
