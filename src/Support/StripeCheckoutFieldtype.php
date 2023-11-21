<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Support;

use Illuminate\Support\Facades\Session;
use Statamic\Fields\Field;
use Statamic\Forms\Form;
use Statamic\Forms\Submission;

class StripeCheckoutFieldtype
{
    public function doesFormHaveStripeCheckout(Form $form)
    {
        return $form->blueprint()
            ->fields()
            ->all()
            ->contains(fn (Field $field) => $field->type() === 'stripe_checkout');
    }

    public function getSecret()
    {
        return config('statamic-stripe-checkout-fieldtype.secret');
    }

    public function getCpCurrency()
    {
        return config('statamic-stripe-checkout-fieldtype.cp_currency');
    }

    public function getSubmissionFromSession(string $form_handle, string $checkout_session_id): ?Submission
    {
        $form = \Statamic\Facades\Form::find($form_handle);

        // form not found
        if (! $form) {
            return null;
        }

        // build the session key
        $key = $this->getSubmissionSessionKey($checkout_session_id);

        if (Session::has($key)) {
            $submission_id = Session::get($key);

            return $form->submission($submission_id);
        }

        // made it this far, not found
        return null;
    }

    public function getSubmissionSessionKey(string $checkout_session_id): string
    {
        return 'stripe_checkout_'.$checkout_session_id;
    }

    public function getSubmissionByCheckoutSessionId(string $form_handle, string $checkout_session_id): ?Submission
    {
        $form = \Statamic\Facades\Form::find($form_handle);

        // form not found
        if (! $form) {
            return null;
        }

        // get the field handle
        $handle = $this->getStripeCheckoutFieldHandle($form);

        return $form->submissions()->first(function ($submission) use ($handle, $checkout_session_id) {
            // get the value
            $value = $submission->data()->get($handle);

            // if we have an array, and a session id, let's check if it is a match
            if (is_array($value) && array_key_exists('checkout_session_id', $value)) {
                return $value['checkout_session_id'] === $checkout_session_id;
            }

            // make it this far, not found
            return false;
        });
    }

    public function getStripeCheckoutFieldHandle(Form $form)
    {
        return $form->blueprint()
            ->fields()
            ->all()
            ->first(fn (Field $field) => $field->type() === 'stripe_checkout')?->handle();
    }
}
