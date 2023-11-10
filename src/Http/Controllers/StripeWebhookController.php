<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    protected $events = [
        'plan.created',
        'plan.deleted',
        'plan.updated',
        'price.created',
        'price.deleted',
        'price.updated',
        'product.created',
        'product.deleted',
        'product.updated',
    ];

    public function __invoke(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        // if this is one of the events to listen for, clear the cache
        if (in_array($payload['type'], $this->events)) {
            // set max retries
            $this->setMaxNetworkRetries();

            // clear the stripe cache
            $this->clearStripeCache();

            return new Response('Webhook Handled', 200);
        }

        // unknown event
        return new Response;
    }

    /**
     * Set the number of automatic retries due to an object lock timeout from Stripe.
     *
     * @param  int  $retries
     * @return void
     */
    protected function setMaxNetworkRetries($retries = 3)
    {
        Stripe::setMaxNetworkRetries($retries);
    }

    protected function clearStripeCache()
    {
        app(StripeService::class)->clearCache();
    }
}
