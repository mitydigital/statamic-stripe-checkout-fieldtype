<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Tests;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;
use MityDigital\StatamicStripeCheckoutFieldtype\ServiceProvider;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\Statamic;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected $shouldFakeVersion = true;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(
            Encrypter::generateKey($app['config']['app.cipher'])
        ));

        // set the forms folder
        $app['config']->set('statamic.forms.forms', __DIR__.'/__fixtures__/forms');

        // set the submissions folder
        $app['config']->set('statamic.forms.submissions', __DIR__.'/__fixtures__/submissions');

        // content
        $app['config']->set('statamic.stache.stores.collections.directory', __DIR__.'/__fixtures__/content/collections');
        $app['config']->set('statamic.stache.stores.entries.directory', __DIR__.'/__fixtures__/content/collections');
        $app['config']->set('statamic.stache.stores.collection-trees.directory', __DIR__.'/__fixtures__/content/trees/collections');

        // build fieldtype config
        $app['config']->set('statamic-stripe-checkout-fieldtype', require (__DIR__.'/../config/statamic-stripe-checkout-fieldtype.php'));

        $app['config']->set('auth.providers.users.driver', 'statamic');
        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('statamic.users.repository', 'file');

        Statamic::booted(function () {
            Blueprint::setDirectory(__DIR__.'/__fixtures__/blueprints');

            // configure to be an AU site
            Site::setSites([
                'default' => [
                    'name' => config('app.name'),
                    'locale' => 'en_AU',
                    'url' => '/',
                ],
            ]);
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
