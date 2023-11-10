<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Listeners;

use MityDigital\StatamicStripeCheckoutFieldtype\Facades\StripeCheckoutFieldtype;
use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;
use Statamic\Facades\Form;
use Statamic\Forms\Submission;

class FormSubmittedListener
{
    public function handle($event): void
    {
        // redirect?
        if (StripeCheckoutFieldtype::doesFormHaveStripeCheckout($event->submission->form())) {
            Form::redirect($event->submission->form()->handle(), function (Submission $submission) {
                return app(StripeService::class)->createCheckoutSession($submission);
            });
        }
    }
}
