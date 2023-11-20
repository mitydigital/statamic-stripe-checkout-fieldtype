<?php

return [
    'title' => 'Stripe Checkout',

    'config' => [

        'currency_code' => [
            'display' => 'Currency Code',
            'instructions' => 'A three-letter <a href=":link" target="_blank">ISO currency code</a> supported by Stripe.',
        ],

        'prices' => [
            'display' => 'Prices',
            'instructions' => 'Creating a Price association uses the defined price for the line item in Stripe.<br><br>When the "Quantity Handle" field has a positive integer value, it will be sent to Stripe.',

            'fields' => [
                'price_id' => 'Stripe Price ID',
                'handle' => 'Quantity Handle',
            ],

            'add_row' => 'Add Price',
        ],

        'products' => [
            'display' => 'Products',
            'instructions' => 'Creating a Price association uses the defined price for the line item in Stripe.<br><br>When the "Value Handle" field has a positive numeric value, it will be sent to Stripe as a new Price object for the Product, with a set quantity of 1.',

            'fields' => [
                'product_id' => 'Stripe Product ID',
                'handle' => 'Value Handle',
            ],

            'add_row' => 'Add Product',
        ],

        'mode' => [
            'display' => 'Default Mode',
            'instructions' => 'Define the default type of checkout to create.',

            'options' => [
                'payment' => 'One-time payment',
                'subscription' => 'Subscription',
            ],
        ],

        'mode_choice' => [
            'display' => 'Choose mode?',
            'instructions' => 'Can the user choose between Payment and Subscription?',
            'options' => [
                'yes' => 'User to decide',
                'no' => 'Always use Default Mode',
            ],
        ],

        'recurring_interval' => [
            'display' => 'Recurring interval',
            'instructions' => 'Only applies to Products. Defines the recurring interval period type.',
            'options' => [
                'day' => 'Day',
                'week' => 'Week',
                'month' => 'Month',
                'year' => 'Year',
            ],
        ],

        'recurring_interval_count' => [
            'display' => 'Recurring interval count',
            'instructions' => 'Only applies to Products. Defines the duration of the interval period.',
        ],

        'allow_promotion_codes' => [
            'display' => 'Allow Promotion Codes?',

            'options' => [
                'no' => 'No',
                'yes' => 'Yes',
            ],
        ],

        'customer_email' => [
            'display' => 'Email Address Field Handle',
            'instructions' => 'The handle of the field in your form that collects the customer email address. Leave empty to not send the Customer Email to Stripe.',
        ],

        'customer_creation' => [
            'display' => 'Create Stripe Customer?',
            'instructions' => 'Should a new Customer be created in Stripe? When set to "If required", a Customer will be created for a Subscription, but not a One-time Payment.',

            'options' => [
                'always' => 'Always',
                'if_required' => 'If required',
            ],
        ],

        'success_url' => [
            'display' => 'Success URL',
            'instructions' => 'The URL to which Stripe should send customers when checkout is complete.',
        ],

        'success_url_include_session' => [
            'display' => 'Include Checkout Session ID with Success URL?',
            'instructions' => 'When enabled, the Checkout Session ID will be included in the Success URL to allow you to personalise the "success" page, if you wish.',

            'options' => [
                'no' => 'No',
                'yes' => 'Yes',
            ],
        ],

        'cancel_url' => [
            'display' => 'Cancel URL',
            'instructions' => 'If set, Checkout displays a back button and customers will be directed to this URL if they decide to cancel payment and return to your website.',
        ],
    ],

    'errors' => [
        'cp' => 'The Stripe Checkout fieldtype is designed for use in your Forms blueprints only.',
    ],
];
