<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Forms\Form;
use Statamic\Forms\Submission;

/**
 * @method static string getSubmissionSessionKey(string $checkout_session_id)
 * @method static null|Submission getSubmissionFromSession(string $form_handle, string $checkout_session_id)
 * @method static null|Submission getSubmissionByCheckoutSessionId(string $form_handle, string $checkout_session_id)
 * @method static bool doesFormHaveStripeCheckout(Form $form)
 * @method static null|string getStripeCheckoutFieldHandle(Form $form)
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
