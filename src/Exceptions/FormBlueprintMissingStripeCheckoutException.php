<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Exceptions;

use Exception;

class FormBlueprintMissingStripeCheckoutException extends Exception
{
    protected $message = 'The submission\'s Form Blueprint does not include a Stripe Checkout fieldtype.';
}
