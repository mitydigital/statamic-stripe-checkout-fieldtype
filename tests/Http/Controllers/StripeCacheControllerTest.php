<?php

use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;
use Statamic\Auth\File\User;

beforeEach(function () {
    $this->user = User::make()
        ->makeSuper()
        ->set('name', 'Peter Parker')
        ->email('peter.parker@spiderman.com')
        ->set('password', 'secret')
        ->save();

    $this->actingAs($this->user);
});

it('clears the cache', function () {
    $spy = $this->spy(StripeService::class);

    $this->get(route('statamic.cp.stripe-checkout.cache.clear'))
        ->assertStatus(302);

    $spy->shouldHaveReceived('clearCache')->once();
});

it('clears and refreshes the cache', function () {
    $spy = $this->spy(StripeService::class);

    $this->get(route('statamic.cp.stripe-checkout.cache.refresh'))
        ->assertStatus(302);

    $spy->shouldHaveReceived('clearCache')->once();
    $spy->shouldHaveReceived('getProducts')->once();
});
