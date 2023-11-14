<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Fieldtypes;

use MityDigital\StatamicStripeCheckoutFieldtype\Support\StripeService;
use NumberFormatter;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class StripeCheckoutFieldtype extends Fieldtype
{
    protected $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.9" d="m19.075 86.745 5.15 5.16 5.16-5.16 5.15 5.16 5.16-5.16 5.15 5.16 5.16-5.16 5.15 5.16 5.16-5.16 5.15 5.16 5.15-5.16 5.16 5.16 5.15-5.16v-73.5l-5.15-5.15-5.16 5.15-5.15-5.15-5.15 5.15-5.16-5.15-5.15 5.15-5.16-5.15-5.15 5.15-5.16-5.15-5.15 5.15-5.16-5.15-5.15 5.15v73.5z" /><path fill="currentColor" d="M38.855 28.755c-.1-.93-.52-1.66-1.25-2.18-.74-.52-1.69-.78-2.87-.78-.83 0-1.53.12-2.12.37-.59.25-1.04.59-1.36 1.01-.31.43-.47.91-.48 1.46 0 .46.1.85.31 1.18s.5.62.86.85c.36.23.76.43 1.21.58.44.16.89.29 1.33.4l2.05.51c.83.19 1.62.45 2.39.78.76.33 1.45.74 2.06 1.24.61.5 1.09 1.1 1.45 1.8.36.7.53 1.53.53 2.48 0 1.28-.33 2.41-.98 3.38s-1.6 1.73-2.83 2.27-2.73.82-4.48.82-3.18-.26-4.42-.79c-1.25-.53-2.22-1.29-2.92-2.31-.7-1.01-1.08-2.24-1.14-3.69h3.9c.06.76.29 1.39.7 1.9s.95.88 1.62 1.13c.67.25 1.41.37 2.24.37s1.62-.13 2.27-.39 1.16-.62 1.53-1.09c.37-.47.56-1.01.57-1.64 0-.57-.17-1.04-.5-1.41-.33-.37-.78-.69-1.37-.94s-1.27-.48-2.05-.68l-2.49-.64c-1.8-.46-3.22-1.17-4.26-2.11-1.04-.94-1.56-2.2-1.56-3.76 0-1.29.35-2.42 1.05-3.38.7-.97 1.66-1.72 2.87-2.26 1.21-.54 2.58-.81 4.11-.81s2.91.27 4.08.81 2.09 1.28 2.76 2.23c.67.95 1.01 2.04 1.04 3.27h-3.81l-.04.02z" /><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="3.9" d="M34.575 21.195v1.71m0 20.84v1.72" /><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="3.9" d="M51.785 26.495h17.46" /><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="3" d="M51.785 34.095h9.52" /><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="3.9" d="M27.795 54.825h43.53m-43.53 9.89h43.53m-43.53 9.88h43.53"/></svg>';

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
                'hide_display' => true,
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
