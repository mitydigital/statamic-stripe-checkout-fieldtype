<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Support;

use Statamic\Fields\Field;
use Statamic\Forms\Form;

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
}
