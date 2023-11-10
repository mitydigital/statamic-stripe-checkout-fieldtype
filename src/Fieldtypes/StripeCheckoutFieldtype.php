<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Fieldtypes;

use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;
use NumberFormatter;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class StripeCheckoutFieldtype extends Fieldtype
{
    protected $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path stroke="currentColor" d="M81.084 58.066H31.027L19.549 14.064H6.1V11h15.818l11.478 44.002H78.98l12.06-31.197 2.859 1.103-12.816 33.158z"/><path stroke="currentColor" d="M81.554 68.175H26.615l4.105-12.172 2.91.97-2.746 8.139h50.67v3.063zM70.505 90.018c-4.697 0-8.517-3.82-8.517-8.517 0-4.697 3.82-8.516 8.517-8.516 4.697 0 8.517 3.819 8.517 8.516 0 4.698-3.82 8.517-8.517 8.517zm0-13.97a5.458 5.458 0 0 0-5.453 5.453 5.459 5.459 0 0 0 5.453 5.453c3.002 0 5.453-2.44 5.453-5.453s-2.44-5.453-5.453-5.453zm-35.332 13.97c-4.698 0-8.517-3.82-8.517-8.517 0-4.697 3.82-8.516 8.517-8.516s8.516 3.819 8.516 8.516c0 4.698-3.819 8.517-8.516 8.517zm0-13.97a5.458 5.458 0 0 0-5.453 5.453c0 3.003 2.44 5.453 5.453 5.453s5.453-2.44 5.453-5.453-2.44-5.453-5.453-5.453zm18.646-35.067L43.393 30.555l2.165-2.165 8.261 8.262 16.615-16.615 2.164 2.165-18.779 18.78z"/></svg>';

    protected $categories = ['special'];

    protected $selectableInForms = true;

    public static function title()
    {
        return __('statamic-stripe-checkout-fieldtype::fieldtype.title');
    }

    public function view()
    {
        return 'statamic-stripe-checkout-fieldtype::forms.fields.stripe_checkout';
    }

    public function preProcess($value)
    {
        // if we have a parent, we're part of the CP blueprints (such as a Collection blueprint)
        if ($this->field()->parent()) {
            return [
                'message' => __('statamic-stripe-checkout-fieldtype::fieldtype.errors.cp'),
            ];
        }

        if ($value) {
            return $this->formatNumber($value);
        }

        return $value;
    }

    public function extraRenderableFieldData(): array
    {
        if ($this->config('mode_choice') === 'no') {
            return [
                // force the field to be hidden - it is the developer's job to honour this
                'hide_display' => 'true',
            ];
        }

        return [];
    }

    protected function configFieldItems(): array
    {
        // get products from the stripe service
        $products = app(StripeService::class)->getProducts();

        return [
            'currency_code' => [
                'type' => 'text',
                'character_limit' => 3,
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.currency_code.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.currency_code.instructions',
                    [
                        'link' => 'https://www.iso.org/iso-4217-currency-codes.html',
                    ]),

                'validate' => [
                    'required',
                    'size:3',
                ],
            ],

            'prices' => [
                'mode' => 'grid',
                'add_row' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.prices.add_row'),
                'reorderable' => true,
                'fullscreen' => false,
                'type' => 'grid',
                'max_rows' => 10,
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.prices.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.prices.instructions'),

                'fields' => [
                    [
                        'handle' => 'price_id',
                        'field' => [
                            'width' => 66,
                            'type' => 'select',
                            'taggable' => true,
                            'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.prices.fields.price_id'),
                            'validate' => ['required'],
                            'options' => $products->map(fn ($product) => $product['prices']->map(function ($price) use (
                                $product
                            ) {
                                // get the name
                                $name = $price['name'];

                                // format the currency
                                $amount = NumberFormatter::create(Site::current()->locale(), NumberFormatter::CURRENCY)
                                    ->formatCurrency($price['amount'],
                                        \MityDigital\StatamicStripeCheckoutFieldtype\Facades\StripeCheckoutFieldtype::getCpCurrency());

                                // set the name string
                                if ($name) {
                                    $name = $name.' ('.$amount.')';
                                } else {
                                    $name = $amount;
                                }

                                return ['value' => $price['id'], 'label' => $product['name'].': '.$name];
                            }))
                                ->flatten(1)
                                ->mapWithKeys(fn ($price) => [$price['value'] => $price['label']]),
                        ],
                    ],
                    [
                        'handle' => 'handle',
                        'field' => [
                            'width' => 33,
                            'type' => 'text',
                            'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.prices.fields.handle'),
                        ],
                    ],
                ],
            ],

            'products' => [
                'mode' => 'grid',
                'add_row' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.products.add_row'),
                'reorderable' => true,
                'fullscreen' => false,
                'type' => 'grid',
                'max_rows' => 10,
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.products.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.products.instructions'),

                'fields' => [
                    [
                        'handle' => 'product_id',
                        'field' => [
                            'width' => 66,
                            'type' => 'select',
                            'taggable' => true,
                            'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.products.fields.product_id'),
                            'validate' => ['required'],
                            'options' => $products
                                ->mapWithKeys(fn ($product) => [$product['id'] => $product['name']])
                                ->sort(),
                        ],
                    ],
                    [
                        'handle' => 'handle',
                        'field' => [
                            'width' => 33,
                            'type' => 'text',
                            'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.products.fields.handle'),
                        ],
                    ],
                ],
            ],

            'mode' => [
                'type' => 'select',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.mode.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.mode.instructions'),
                'options' => [
                    'payment' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.mode.options.payment'),
                    'subscription' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.mode.options.subscription'),
                ],
                'default' => 'payment',

                'validate' => [
                    'required',
                ],
            ],

            'mode_choice' => [
                'type' => 'select',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.mode_choice.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.mode_choice.instructions'),
                'options' => [
                    'no' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.mode_choice.options.no'),
                    'yes' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.mode_choice.options.yes'),
                ],
                'default' => 'yes',

                'validate' => [
                    'required',
                ],
            ],

            'recurring_interval' => [
                'type' => 'select',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.recurring_interval.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.recurring_interval.instructions'),

                'options' => [
                    'day' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.recurring_interval.options.day'),
                    'week' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.recurring_interval.options.week'),
                    'month' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.recurring_interval.options.month'),
                    'year' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.recurring_interval.options.year'),
                ],

                'default' => 'month',

                'if_any' => [
                    'mode_choice' => 'equals yes',
                    'mode' => 'equals subscription',
                ],
            ],

            'recurring_interval_count' => [
                'type' => 'text',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.recurring_interval_count.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.recurring_interval_count.instructions'),

                'default' => 1,

                'validate' => [
                    'required',
                    'integer',
                ],

                'if_any' => [
                    'mode_choice' => 'equals yes',
                    'mode' => 'equals subscription',
                ],
            ],

            'allow_promotion_codes' => [
                'type' => 'select',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.allow_promotion_codes.display'),
                'options' => [
                    'no' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.allow_promotion_codes.options.no'),
                    'yes' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.allow_promotion_codes.options.yes'),
                ],
                'default' => 'no',

                'validate' => [
                    'required',
                ],
            ],

            'customer_email' => [
                'type' => 'text',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.customer_email.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.customer_email.instructions'),
            ],

            'customer_creation' => [
                'type' => 'select',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.customer_creation.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.customer_creation.instructions'),
                'options' => [
                    'always' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.customer_creation.options.always'),
                    'if_required' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.customer_creation.options.if_required'),
                ],
                'default' => 'always',

                'validate' => [
                    'required',
                ],
            ],

            'success_url' => [
                'type' => 'link',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.success_url.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.success_url.instructions'),

                'validate' => [
                    'required',
                ],
            ],

            'success_url_include_session' => [
                'type' => 'select',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.success_url_include_session.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.success_url_include_session.instructions'),
                'options' => [
                    'no' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.success_url_include_session.options.no'),
                    'yes' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.success_url_include_session.options.yes'),
                ],
                'default' => 'no',

                'validate' => [
                    'required',
                ],
            ],

            'cancel_url' => [
                'type' => 'link',
                'display' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.cancel_url.display'),
                'instructions' => __('statamic-stripe-checkout-fieldtype::fieldtype.config.cancel_url.instructions'),
            ],
        ];
    }
}
