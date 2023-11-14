<?php

use MityDigital\StatamicStripeCheckoutFieldtype\Fieldtypes\StripeCheckoutFieldtype;
use MityDigital\StatamicStripeCheckoutFieldtype\Listeners\FormSubmittedListener;
use MityDigital\StatamicStripeCheckoutFieldtype\ServiceProvider;
use Statamic\Events\FormSubmitted;
use Statamic\Facades\Permission;

it('correctly registers the fieldtype', function () {
    $fieldtypes = getPrivateProperty(ServiceProvider::class, 'fieldtypes');
    expect($fieldtypes->getDefaultValue())
        ->toMatchArray([
            StripeCheckoutFieldtype::class,
        ]);
});

it('correctly registers listening for the FormSubmitted event', function () {
    $listen = getPrivateProperty(ServiceProvider::class, 'listen');
    expect($listen->getDefaultValue())
        ->toMatchArray([
            FormSubmitted::class => [
                FormSubmittedListener::class,
            ],
        ]);
});

it('correctly registers web and cp routes', function () {
    $routes = getPrivateProperty(ServiceProvider::class, 'routes');
    expect($routes->getDefaultValue())
        ->toHaveKeys([
            'web',
            'cp',
        ]);
});

it('correctly registers for vite', function () {
    $vite = getPrivateProperty(ServiceProvider::class, 'vite');
    expect($vite->getDefaultValue())
        ->toHaveKeys([
            'input',
            'publicDirectory',
        ])
        ->toMatchArray([
            'input' => [
                'resources/js/cp.js',
            ],
            'publicDirectory' => 'resources/dist',
        ]);
});

it('registers the cache permission', function () {
    expect(Permission::get('manage stripe checkout products cache'))
        ->not()->toBeNull()
        ->toBeInstanceOf(\Statamic\Auth\Permission::class);
});
