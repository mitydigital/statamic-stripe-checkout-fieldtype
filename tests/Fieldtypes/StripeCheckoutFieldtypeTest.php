<?php

use MityDigital\StatamicStripeCheckoutFieldtype\Fieldtypes\StripeCheckoutFieldtype;
use MityDigital\StatamicStripeCheckoutFieldtype\Tests\MockStripeClient;
use Statamic\Entries\Entry;
use Statamic\Facades\Blueprint;
use Stripe\ApiRequestor;

beforeEach(function () {
    ApiRequestor::setHttpClient(new MockStripeClient());
    $this->fieldtype = app(StripeCheckoutFieldtype::class);
});

//
// view
//
it('returns the correct view', function () {
    // load the blueprint
    $blueprint = Blueprint::find('forms/advanced');

    // get the stripe checkout
    $field = $blueprint->field('frequency');

    expect($field->fieldtype()->view())->toBe('statamic-stripe-checkout-fieldtype::forms.fields.stripe_checkout');
});

//
// formatNumber
//
it('formats numbers', function () {
    // load the blueprint
    $blueprint = Blueprint::find('forms/advanced');

    // get the variable number
    $field = $blueprint->field('frequency');

    expect(callProtectedMethod($field->fieldtype(), 'formatNumber', [1.5]))
        ->toBe('$1.50')
        ->and(callProtectedMethod($field->fieldtype(), 'formatNumber', [1.0]))
        ->toBe('$1.00')
        ->and(callProtectedMethod($field->fieldtype(), 'formatNumber', [100]))
        ->toBe('$100.00');
});

//
// preProcess
//
it('correctly returns an error message if part of a non-form blueprint', function () {
    // load the blueprint
    $blueprint = Blueprint::find('collections/pages/article');

    // set the parent
    $blueprint->setParent(new Entry());

    // get the variable number
    $field = $blueprint->field('frequency');

    // expect an error array
    expect($field->fieldtype()->preProcess(1))
        ->toBeArray()
        ->toHaveKey('message');
});

it('returns the language string for view', function () {
    // load the blueprint
    $blueprint = Blueprint::find('forms/advanced');

    // get the variable number
    $field = $blueprint->field('frequency');

    expect(callProtectedMethod($field->fieldtype(), 'getLabel', ['payment']))
        ->toBe(__('statamic-stripe-checkout-fieldtype::fieldtype.config.mode.options.payment'))
        ->and(callProtectedMethod($field->fieldtype(), 'getLabel', ['subscription']))
        ->toBe(__('statamic-stripe-checkout-fieldtype::fieldtype.config.mode.options.subscription'))
        ->and(callProtectedMethod($field->fieldtype(), 'getLabel', ['something else']))
        ->toBe('something else');
});

//
// preProcessIndex
//
it('returns the language string for index', function () {
    // load the blueprint
    $blueprint = Blueprint::find('forms/advanced');

    // get the variable number
    $field = $blueprint->field('frequency');

    expect($field->fieldtype()->preProcessIndex('payment'))
        ->toBe(__('statamic-stripe-checkout-fieldtype::fieldtype.config.mode.options.payment'))
        ->and($field->fieldtype()->preProcessIndex('subscription'))
        ->toBe(__('statamic-stripe-checkout-fieldtype::fieldtype.config.mode.options.subscription'))
        ->and($field->fieldtype()->preProcessIndex('something else'))
        ->toBe('something else');
});

//
// extraRenderableFieldData
//
it('correctly sets hide display when no choice is available', function () {
    // load the blueprint
    $blueprint = Blueprint::find('forms/advanced');

    // get the stripe checkout
    $field = $blueprint->field('frequency');

    $config = $field->fieldtype()->config();
    $extra = $field->fieldtype()->extraRenderableFieldData();

    expect($config)->toHaveKey('mode_choice')
        ->and($config['mode_choice'])
        ->toBe('no')
        ->and($extra)
        ->toHaveKey('hide_display')
        ->and($extra['hide_display'])
        ->toBeTrue();
});

it('correctly does not hide display when a choice is available', function () {
    // load the blueprint
    $blueprint = Blueprint::find('forms/has_stripe_checkout_fieldtype_price');

    // get the stripe checkout
    $field = $blueprint->field('frequency');

    $config = $field->fieldtype()->config();
    $extra = $field->fieldtype()->extraRenderableFieldData();

    expect($config)->toHaveKey('mode_choice')
        ->and($config['mode_choice'])
        ->toBe('yes')
        ->and($extra)
        ->not()->toHaveKey('hide_display');
});

//
// configFieldItems
//
it('has the expected configuration options', function () {
    $config = callProtectedMethod($this->fieldtype, 'configFieldItems');

    expect($config)
        ->toHaveKeys([
            'currency_code',
            'prices',
            'products',
            'mode',
            'mode_choice',
            'recurring_interval',
            'recurring_interval_count',
            'allow_promotion_codes',
            'customer_email',
            'customer_creation',
            'success_url',
            'success_url_include_session',
            'cancel_url',
        ]);

    // currency
    expect($config['currency_code']['validate'])
        ->toMatchArray([
            'required',
            'size:3',
        ]);

    // recurring_interval
    expect($config['recurring_interval']['if_any'])
        ->toMatchArray([
            'mode_choice' => 'equals yes',
            'mode' => 'equals subscription',
        ]);

    expect($config['recurring_interval_count']['if_any'])
        ->toMatchArray([
            'mode_choice' => 'equals yes',
            'mode' => 'equals subscription',
        ])
        ->and($config['recurring_interval_count']['validate'])
        ->toMatchArray([
            'required',
            'integer',
        ]);
});
