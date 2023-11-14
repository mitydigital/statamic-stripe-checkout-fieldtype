<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Forms\Form;

/**
 * @method static bool doesFormHaveStripeCheckout(Form $form)
 * @method static string getCpCurrency()
 * @method static string getSecret()
 *
 * @see StripeCheckoutFieldtype
 */
class StripeCheckoutFieldtype extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeCheckoutFieldtype::class;
    }
}
