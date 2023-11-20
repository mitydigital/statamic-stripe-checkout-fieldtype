<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use MityDigital\StatamicStripeCheckoutFieldtype\Http\Controllers\StripeWebhookController;
use MityDigital\StatamicStripeCheckoutFieldtype\Http\Middleware\VerifyWebhookSignature;
use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;

it('has the correct middleware', function () {
    $router = app('router');

    $route = collect($router->getRoutes())
        ->filter(fn (Route $route) => $route->getName() === 'stripe-checkout-fieldtype.webhook')
        ->first();

    // check route URI, methods and middleware
    expect($route->uri())
        ->toBe('stripe/webhook')
        ->and($route->methods())
        ->toBeArray()
        ->toHaveCount(1)
        ->toMatchArray(['POST'])
        ->and($route)
        ->not()->toBeNull()
        ->and($route->gatherMiddleware())
        ->toBeArray()
        ->toContain(
            'web',
            VerifyWebhookSignature::class,
        )
        ->not()->toContain(VerifyCsrfToken::class);

});

it('clears the cache when a matched event is received', function () {

    $service = Mockery::mock(StripeService::class);
    $service->shouldReceive('clearCache')->once();

    app()->bind(StripeService::class, function () use ($service) {
        return $service;
    });

    // make the controller
    $controller = app(StripeWebhookController::class);

    // make the request
    $request = Request::create(
        '/', 'POST', [], [], [], [], json_encode(['type' => 'price.created'])
    );

    // invoke
    $response = $controller($request);

    // expect "Webhook handled"
    expect($response->getContent())->toBe('Webhook Handled');
});

it('does nothing when an unknown event is received', function () {

    $service = Mockery::mock(StripeService::class);
    $service->shouldNotHaveReceived('clearCache');

    app()->bind(StripeService::class, function () use ($service) {
        return $service;
    });

    // make the controller
    $controller = app(StripeWebhookController::class);

    // make the request
    $request = Request::create(
        '/', 'POST', [], [], [], [], json_encode(['type' => 'not.an.event'])
    );

    // invoke
    $response = $controller($request);

    // expect ""
    expect($response->getContent())->toBe('');
});
