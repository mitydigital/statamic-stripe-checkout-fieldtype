<?php

use Illuminate\Support\Facades\Route;
use MityDigital\StatamicStripeCheckoutFieldtype\Http\Controllers\StripeCacheController;

Route::get('stripe-checkout/cache',
    [StripeCacheController::class, 'clear'])
    ->name('stripe-checkout.cache.clear')
    ->can('manage stripe checkout products cache');

Route::get('stripe-checkout/refresh',
    [StripeCacheController::class, 'refresh'])
    ->name('stripe-checkout.cache.refresh')
    ->can('manage stripe checkout products cache');
