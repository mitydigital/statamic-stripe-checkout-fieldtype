<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use MityDigital\StatamicStripeCheckoutFieldtype\Http\Controllers\StripeWebhookController;
use MityDigital\StatamicStripeCheckoutFieldtype\Http\Middleware\VerifyWebhookSignature;

Route::post('stripe/webhook', StripeWebhookController::class)
    ->middleware([VerifyWebhookSignature::class])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('stripe-checkout-fieldtype.webhook');
