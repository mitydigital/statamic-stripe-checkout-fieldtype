<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;
use MityDigital\StatamicStripeCheckoutFieldtype\Tests\MockStripeClient;
use Statamic\Facades\Form;
use Statamic\Facades\URL;
use Stripe\ApiRequestor;
use Stripe\StripeClient;

beforeEach(function () {
    ApiRequestor::setHttpClient(new MockStripeClient());

    $this->support = app(StripeService::class);
});

it('returns a stripe client', function () {
    expect(callProtectedMethod($this->support, 'getService'))->toBeInstanceOf(StripeClient::class);
});

it('gets products and prices from stripe, and stores them in a cached variable', function () {
    // get the cache key
    $cacheKey = callProtectedMethod($this->support, 'cacheProductsKey');

    // cache should be empty
    expect(Cache::get($cacheKey))->toBeNull();

    // load the products
    $this->support->getProducts();

    // cache should be a collection
    expect(Cache::get($cacheKey))->not()->toBeNull()
        ->toBeInstanceOf(Collection::class);
});

it('clears the cache', function () {
    // get the cache key
    $cacheKey = callProtectedMethod($this->support, 'cacheProductsKey');

    // cache should be empty
    expect(Cache::get($cacheKey))->toBeNull();

    // load the products
    $this->support->getProducts();

    // cache should be a collection
    expect(Cache::get($cacheKey))->not()->toBeNull()
        ->toBeInstanceOf(Collection::class);

    // clear the cache
    $this->support->clearCache();

    // cache should be empty
    expect(Cache::get($cacheKey))->toBeNull();
});

it('converts an entry reference to an absolute url', function () {
    $entry = 'entry::78063fba-60b8-4fd5-9cc9-b6ef4ac336c1';

    $url = callProtectedMethod($this->support, 'getUrl', [
        $entry,
        false,
    ]);

    expect($url)
        ->toBe(URL::makeAbsolute('/testable'));
});

it('converts an entry reference to an absolute url and adds the session id', function () {
    $entry = 'entry::78063fba-60b8-4fd5-9cc9-b6ef4ac336c1';

    $url = callProtectedMethod($this->support, 'getUrl', [
        $entry,
        true,
    ]);

    expect($url)
        ->toBe(URL::makeAbsolute('/testable?session_id={CHECKOUT_SESSION_ID}'));
});

it('correctly returns an absolute url', function () {
    $url = callProtectedMethod($this->support, 'getUrl', [
        'https://www.mity.com.au',
        false,
    ]);

    expect($url)
        ->toBe('https://www.mity.com.au');
});

it('correctly returns an absolute url and adds the session id', function () {
    $url = callProtectedMethod($this->support, 'getUrl', [
        'https://www.mity.com.au',
        true,
    ]);

    expect($url)
        ->toBe('https://www.mity.com.au?session_id={CHECKOUT_SESSION_ID}');

    // with an existing query string, join the session with an &
    $url = callProtectedMethod($this->support, 'getUrl', [
        'https://www.mity.com.au?existing=query_string',
        true,
    ]);

    expect($url)
        ->toBe('https://www.mity.com.au?existing=query_string&session_id={CHECKOUT_SESSION_ID}');
});

it('creates a checkout url', function () {
    $form = Form::find('has_stripe_checkout_fieldtype');
    $submission = $form->submission('1699931909.6729');

    // make sure the submission is real
    expect($submission)
        ->not()->toBeNull();

    // get the url
    $url = $this->support->createCheckoutSession($submission);

    expect($url)
        ->not()->toBeFalse()
        ->toBeString()
        ->toBeUrl($url);
});

it('throws an exception when there are no line items', function () {
    $form = Form::find('has_stripe_checkout_fieldtype');
    $submission = $form->submission('1699921970.0253');

    // make sure the submission is real
    expect($submission)
        ->not()->toBeNull();

    // get the url
    $url = $this->support->createCheckoutSession($submission);

    expect($url)->toBeFalse();
});

it('correctly creates a payload for a product', function () {
    $form = Form::find('has_stripe_checkout_fieldtype');
    $submission = $form->submission('1699931909.6729');

    // make sure the submission is real
    expect($submission)
        ->not()->toBeNull();

    // get the payload
    $payload = callProtectedMethod($this->support, 'buildCheckoutPayloadFromSubmission', [$submission]);

    // check we're correct
    expect($payload)->toHaveKeys([
        'client_reference_id',
        'mode',
        'success_url',
        'currency',
        'customer_creation',
        'line_items',
    ])
        ->and($payload['client_reference_id'])->toBe('1699931909.6729')
        ->and($payload['mode'])->toBe('payment')
        ->and($payload['success_url'])->toBe('https://www.mity.com.au')
        ->and($payload['currency'])->toBe('AUD')
        ->and($payload['customer_creation'])->toBe('always')
        ->and($payload['line_items'])->toBeArray()->toHaveCount(1)
        ->and($payload['line_items'][0])->toMatchArray([
            'price_data' => [
                'currency' => 'AUD',
                'product' => 'prod_Oyk7eIZVR0Hwag',
                'unit_amount' => 2000,
            ],
            'quantity' => 1,
        ]);

});

it('correctly creates a payload for a price', function () {
    $form = Form::find('has_stripe_checkout_fieldtype_price');
    $submission = $form->submission('1699931909.6730');

    // make sure the submission is real
    expect($submission)
        ->not()->toBeNull();

    // get the payload
    $payload = callProtectedMethod($this->support, 'buildCheckoutPayloadFromSubmission', [$submission]);

    // check we're correct
    expect($payload)->toHaveKeys([
        'client_reference_id',
        'mode',
        'success_url',
        'currency',
        'line_items',
        'allow_promotion_codes',
        'cancel_url',
    ])
        ->and($payload['client_reference_id'])->toBe('1699931909.6730')
        ->and($payload['mode'])->toBe('subscription')
        ->and($payload['success_url'])->toBe('https://www.mity.com.au')
        ->and($payload['currency'])->toBe('AUD')
        ->and($payload['line_items'])->toBeArray()->toHaveCount(1)
        ->and($payload['line_items'][0])->toMatchArray([
            'price_id' => 'price_1OAmdIK5kFGWTVZLRKwoEJZ8',
            'quantity' => 2,
        ])
        ->and($payload['allow_promotion_codes'])->toBeTrue()
        ->and($payload['cancel_url'])->toBe('https://www.mity.com.au/cancel');
});

it('correctly creates a payload for multiple products and prices', function () {
    $form = Form::find('advanced');
    $submission = $form->submission('1699932840.2193');

    // make sure the submission is real
    expect($submission)
        ->not()->toBeNull();

    // get the payload
    $payload = callProtectedMethod($this->support, 'buildCheckoutPayloadFromSubmission', [$submission]);

    // check we're correct
    expect($payload)->toHaveKeys([
        'client_reference_id',
        'mode',
        'success_url',
        'currency',
        'line_items',
        'cancel_url',
    ])
        ->and($payload['client_reference_id'])->toBe('1699932840.2193')
        ->and($payload['mode'])->toBe('subscription')
        ->and($payload['success_url'])->toBe('https://www.mity.com.au')
        ->and($payload['currency'])->toBe('AUD')
        ->and($payload['line_items'])->toBeArray()->toHaveCount(3)
        ->and($payload['line_items'][0])->toMatchArray([
            'price_id' => 'price_1OAmdIK5kFGWTVZLRKwoEJZ8',
            'quantity' => 2,
        ])
        ->and($payload['line_items'][1])->toMatchArray([
            'price_id' => 'price_1OAmdIK5kFGWTVZLdoYBToIf',
            'quantity' => 3,
        ])
        ->and($payload['line_items'][2])->toMatchArray([
            'price_data' => [
                'currency' => 'AUD',
                'product' => 'prod_Oyk7eIZVR0Hwag',
                'unit_amount' => 100,
                'recurring' => [
                    'interval' => 'week',
                    'interval_count' => 1,
                ],
            ],
            'quantity' => 1,
        ])
        ->and($payload['cancel_url'])->toBe('https://www.mity.com.au/cancel');
});

it('creates a webhook', function () {
    // create the webhook
    $response = $this->support->createWebhook();

    // ensure we are NOT a string, and we are true
    expect($response)
        ->not()->toBeString()
        ->toBeTrue();
});
