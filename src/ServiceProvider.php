<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype;

use MityDigital\StatamicStripeCheckoutFieldtype\Console\Commands\CreateWebhookCommand;
use MityDigital\StatamicStripeCheckoutFieldtype\Fieldtypes\StripeCheckoutFieldtype;
use MityDigital\StatamicStripeCheckoutFieldtype\Listeners\FormSubmittedListener;
use Statamic\Events\FormSubmitted;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        CreateWebhookCommand::class,
    ];

    protected $fieldtypes = [
        StripeCheckoutFieldtype::class,
    ];

    protected $listen = [
        FormSubmitted::class => [
            FormSubmittedListener::class,
        ],
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/cp.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon()
    {
        //
        // set up the facade
        //
        $this->app->bind('StripeCheckoutFieldtype', function () {
            return new Support\StripeCheckoutFieldtype();
        });

        // views
        $this->publishes([
            __DIR__.'/../resources/views/forms/fields' => resource_path('views/vendor/statamic-stripe-checkout-fieldtype/forms/fields'),
        ], 'statamic-stripe-checkout-fieldtype-views');

        // define nav
        Nav::extend(function ($nav) {
            $nav->content(__('statamic-stripe-checkout-fieldtype::nav.clear'))
                ->section(__('statamic-stripe-checkout-fieldtype::nav.section'))
                ->route('stripe-checkout.cache.clear')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"  stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>');
        });

        Nav::extend(function ($nav) {
            $nav->content(__('statamic-stripe-checkout-fieldtype::nav.refresh'))
                ->section(__('statamic-stripe-checkout-fieldtype::nav.section'))
                ->route('stripe-checkout.cache.refresh')
                ->icon('synchronize');
        });

        // define permissions
        Permission::register('manage stripe checkout products cache')
            ->label(__('statamic-stripe-checkout-fieldtype::permissions.cache'));

    }
}
