<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Http\Controllers;

use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;
use Statamic\Facades\CP\Toast;
use Statamic\Http\Controllers\CP\CpController;

class StripeCacheController extends CpController
{
    public function clear(StripeService $service)
    {
        // clear the cache
        $service->clearCache();

        // say we did
        Toast::success(__('statamic-stripe-checkout-fieldtype::cache.clear'));

        // go back
        return back();
    }

    public function refresh(StripeService $service)
    {
        // clear the cache
        $service->clearCache();

        // get products
        $service->getProducts();

        // say we did
        Toast::success(__('statamic-stripe-checkout-fieldtype::cache.refresh'));

        // go back
        return back();
    }
}
