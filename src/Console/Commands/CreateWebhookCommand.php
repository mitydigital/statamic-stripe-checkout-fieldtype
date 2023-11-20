<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Console\Commands;

use Illuminate\Console\Command;
use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;

class CreateWebhookCommand extends Command
{
    protected $signature = 'stripe-checkout:webhook {--disabled : Disable the webhook after creation}';

    protected $description = 'Create a webhook in Stripe to work with the Stripe Checkout Fieldtype.';

    public function handle(): void
    {
        $service = app(StripeService::class);

        // create the webhook
        $response = $service->createWebhook($this->option('disabled'));

        if ($response === true) {
            $this->info(__('statamic-stripe-checkout-fieldtype::messages.webhook_created'));

            if ($this->option('disabled')) {
                $this->info(__('statamic-stripe-checkout-fieldtype::messages.webhook_disabled'));
            }
        } else {
            $this->error(__('statamic-stripe-checkout-fieldtype::messages.webhook_not_created',
                ['error' => $response]));
        }
    }
}
