<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Tests;

use Facades\Statamic\Version;
use Illuminate\Support\Facades\File;
use MityDigital\StatamicVariableNumberFieldtype\ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Statamic\Console\Processes\Composer;
use Statamic\Extend\Manifest;
use Statamic\Facades\Blueprint;
use Statamic\Providers\StatamicServiceProvider;
use Statamic\Statamic;

abstract class TestCase extends OrchestraTestCase
{
    protected $shouldFakeVersion = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        if ($this->shouldFakeVersion) {
            Version::shouldReceive('get')
                ->andReturn(Composer::create(__DIR__.'/../')->installedVersion(Statamic::PACKAGE));
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            StatamicServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Statamic' => Statamic::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->make(Manifest::class)->manifest = [
            'mitydigital/statamic-stripe-checkout' => [
                'id' => 'mitydigital/statamic-stripe-checkout',
                'namespace' => 'MityDigital\\StatamicStripeCheckout',
            ],
        ];
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'forms',
            'sites',
        ];

        foreach ($configs as $config) {
            $app['config']->set(
                "statamic.$config",
                require(__DIR__."/../vendor/statamic/cms/config/{$config}.php")
            );
        }

        // set the forms folder
        $app['config']->set('statamic.forms.forms', __DIR__.'/__fixtures__/forms');

        // configure to be an AU site
        $app['config']->set('statamic.sites.sites.default.locale', 'en_AU');

        Statamic::booted(function () {
            Blueprint::setDirectory(__DIR__.'/__fixtures__/blueprints');
        });

    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->getTempDirectory());

        parent::tearDown();
    }

    public function getTempDirectory($suffix = ''): string
    {
        return __DIR__.'/TestSupport/'.($suffix == '' ? '' : '/'.$suffix);
    }
}
