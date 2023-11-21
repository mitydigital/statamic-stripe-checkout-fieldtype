<?php

use MityDigital\StatamicStripeCheckoutFieldtype\Facades\StripeCheckoutFieldtype;
use Statamic\Facades\Form;
use Statamic\Forms\Submission;

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

it('correctly gets the stripe checkout fieldtype handle', function () {
    expect(StripeCheckoutFieldtype::getStripeCheckoutFieldHandle(Form::find('has_stripe_checkout_fieldtype')))
        ->toBeString()
        ->toBe('frequency');
});

it('finds a submission with the correct checkout session id', function () {
    expect(StripeCheckoutFieldtype::getSubmissionByCheckoutSessionId(
        'has_stripe_checkout_fieldtype',
        'cs_test_id'
    ))
        ->toBeInstanceOf(Submission::class)
        ->and(StripeCheckoutFieldtype::getSubmissionByCheckoutSessionId(
            'has_stripe_checkout_fieldtype',
            'not_a_cs_test_id'
        ))
        ->toBeNull();
});

it('returns the session key', function () {
    expect(StripeCheckoutFieldtype::getSubmissionSessionKey('cs_id'))
        ->toBeString()
        ->toBe('stripe_checkout_cs_id')
        ->and(StripeCheckoutFieldtype::getSubmissionSessionKey('a_different_cs_id'))
        ->toBeString()
        ->toBe('stripe_checkout_a_different_cs_id');
});
