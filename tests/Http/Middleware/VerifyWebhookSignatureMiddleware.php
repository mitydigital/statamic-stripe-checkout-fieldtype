<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MityDigital\StatamicStripeCheckoutFieldtype\Http\Middleware\VerifyWebhookSignature;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

// based on the logic and testing of cashier's stripe implementation
beforeEach(function () {
    config(['statamic-stripe-checkout-fieldtype.webhook.secret' => 'secret']);
    config(['statamic-stripe-checkout-fieldtype.webhook.tolerance' => 300]);

    $this->request = new Request([], [], [], [], [], [], 'Signed Body');
});

it('is ok when secret matches', function () {
    $timestamp = time();
    $secret = 'secret';
    $signature = hash_hmac('sha256', $timestamp.'.'.$this->request->getContent(), $secret);

    // set headers
    $this->request->headers->set('Stripe-Signature', 't='.$timestamp.',v1='.$signature);

    $response = (new VerifyWebhookSignature())
        ->handle($this->request, function ($request) {
            return new Response('OK');
        });

    $this->assertEquals('OK', $response->content());
});

it('is ok when time is within tolerance', function () {
    $timestamp = time() - 300;
    $secret = 'secret';
    $signature = hash_hmac('sha256', $timestamp.'.'.$this->request->getContent(), $secret);

    // set headers
    $this->request->headers->set('Stripe-Signature', 't='.$timestamp.',v1='.$signature);

    $response = (new VerifyWebhookSignature())
        ->handle($this->request, function ($request) {
            return new Response('OK');
        });

    $this->assertEquals('OK', $response->content());
});

it('aborts when timestamp is too old', function () {
    $timestamp = time() - 301;
    $secret = 'secret';
    $signature = hash_hmac('sha256', $timestamp.'.'.$this->request->getContent(), $secret);

    // set headers
    $this->request->headers->set('Stripe-Signature', 't='.$timestamp.',v1='.$signature);

    $response = (new VerifyWebhookSignature())
        ->handle($this->request, function ($request) {
        });
})->throws(AccessDeniedHttpException::class);

it('aborts when secret does not match', function () {
    $timestamp = time();
    $secret = 'not-the-secret';
    $signature = hash_hmac('sha256', $timestamp.'.'.$this->request->getContent(), $secret);

    // set headers
    $this->request->headers->set('Stripe-Signature', 't='.$timestamp.',v1='.$signature);

    $response = (new VerifyWebhookSignature())
        ->handle($this->request, function ($request) {
        });
})->throws(AccessDeniedHttpException::class);

it('aborts when there is no secret', function () {
    $timestamp = time();
    $secret = '';
    $signature = hash_hmac('sha256', $timestamp.'.'.$this->request->getContent(), $secret);

    // set headers
    $this->request->headers->set('Stripe-Signature', 't='.$timestamp.',v1='.$signature);

    $response = (new VerifyWebhookSignature())
        ->handle($this->request, function ($request) {
        });
})->throws(AccessDeniedHttpException::class);
