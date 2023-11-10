<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Exceptions;

use Exception;

class NoLineItemsException extends Exception
{
    protected $message = 'There are no Line Items for this checkout.';
}
