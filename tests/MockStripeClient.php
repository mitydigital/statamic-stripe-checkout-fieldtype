<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Tests;

use Stripe\HttpClient\ClientInterface;

class MockStripeClient implements ClientInterface
{
    const CODE = 200;

    const HEADER = [];

    public function __construct(
        private string $rbody = '{}',
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function request($method, $absUrl, $headers, $params, $hasFile): array
    {
        if (strtolower($method) === 'post' && str_contains($absUrl, 'https://api.stripe.com/v1/checkout/sessions')) {
            $this->rbody = $this->getCheckoutSession();
        }

        if (strtolower($method) === 'get' && str_contains($absUrl, 'https://api.stripe.com/v1/products/search')) {
            $this->rbody = $this->getProducts();
        }

        if (strtolower($method) === 'get' && str_contains($absUrl, 'https://api.stripe.com/v1/prices')) {
            if ($params['product'] === 'prod_Oyk7MJMkduPIDW') {
                $this->rbody = $this->getPricesForSetPrice();
            }
            if ($params['product'] === 'prod_Oyk7eIZVR0Hwag') {
                $this->rbody = $this->getPricesForVariablePrice();
            }
        }

        return [$this->rbody, self::CODE, self::HEADER];
    }

    private function getCheckoutSession(): string
    {
        return <<<'JSON'
{
    "id": "cs_test_a1mteSGlCo3zRlgDMJY5XIP3UgtaUTxRa3ri9sUXKHnJSo5k89254ujefk",
    "object": "checkout.session",
    "after_expiration": null,
    "allow_promotion_codes": null,
    "amount_subtotal": 2000,
    "amount_total": 2000,
    "automatic_tax": {
        "enabled": false,
        "status": null
    },
    "billing_address_collection": null,
    "cancel_url": null,
    "client_reference_id": "1699932000.6742",
    "client_secret": null,
    "consent": null,
    "consent_collection": null,
    "created": 1699932001,
    "currency": "aud",
    "currency_conversion": null,
    "custom_fields": [],
    "custom_text": {
        "shipping_address": null,
        "submit": null,
        "terms_of_service_acceptance": null
    },
    "customer": null,
    "customer_creation": "always",
    "customer_details": null,
    "customer_email": null,
    "expires_at": 1700018401,
    "invoice": null,
    "invoice_creation": {
        "enabled": false,
        "invoice_data": {
            "account_tax_ids": null,
            "custom_fields": null,
            "description": null,
            "footer": null,
            "metadata": [],
            "rendering_options": null
        }
    },
    "livemode": false,
    "locale": null,
    "metadata": [],
    "mode": "payment",
    "payment_intent": null,
    "payment_link": null,
    "payment_method_collection": "if_required",
    "payment_method_configuration_details": null,
    "payment_method_options": [],
    "payment_method_types": [
        "card"
    ],
    "payment_status": "unpaid",
    "phone_number_collection": {
        "enabled": false
    },
    "recovered_from": null,
    "setup_intent": null,
    "shipping_address_collection": null,
    "shipping_cost": null,
    "shipping_details": null,
    "shipping_options": [],
    "status": "open",
    "submit_type": null,
    "subscription": null,
    "success_url": "https:\/\/www.mity.com.au?session_id={CHECKOUT_SESSION_ID}",
    "total_details": {
        "amount_discount": 0,
        "amount_shipping": 0,
        "amount_tax": 0
    },
    "ui_mode": "hosted",
    "url": "https:\/\/checkout.stripe.com\/c\/pay\/cs_test_a1mteSGlCo3zRlgDMJY5XIP3UgtaUTxRa3ri9sUXKHnJSo5k89254ujefk#fidkdWxOYHwnPyd1blpxYHZxWjA0SkRpcjNOMG5DQlJRU19JYnVJTldtbjdTcjRwfz1PbjFEdzNwR0l%2FN3BxbG9vXHRJZGo3RHRrYjJzZG99dHRXZklra0ZTMld3fUBgRGY8M0tNNHVucGI2NTVoRHxDcmB3XScpJ2N3amhWYHdzYHcnP3F3cGApJ2lkfGpwcVF8dWAnPyd2bGtiaWBabHFgaCcpJ2BrZGdpYFVpZGZgbWppYWB3dic%2FcXdwYHgl"
}
JSON;

    }

    private function getProducts(): string
    {
        return <<<'JSON'
{
    "object": "search_result",
    "data": [
        {
            "id": "prod_Oyk7MJMkduPIDW",
            "object": "product",
            "active": true,
            "attributes": [],
            "created": 1699591671,
            "default_price": "price_1OAmdIK5kFGWTVZLdoYBToIf",
            "description": null,
            "features": [],
            "images": [],
            "livemode": false,
            "metadata": [],
            "name": "Set Price",
            "package_dimensions": null,
            "shippable": null,
            "statement_descriptor": null,
            "tax_code": null,
            "type": "service",
            "unit_label": null,
            "updated": 1699595617,
            "url": null
        },
        {
            "id": "prod_Oyk7eIZVR0Hwag",
            "object": "product",
            "active": true,
            "attributes": [],
            "created": 1699591648,
            "default_price": "price_1OAmcuK5kFGWTVZLhVgqnJD8",
            "description": null,
            "features": [],
            "images": [],
            "livemode": false,
            "metadata": [],
            "name": "Variable Price",
            "package_dimensions": null,
            "shippable": null,
            "statement_descriptor": null,
            "tax_code": null,
            "type": "service",
            "unit_label": null,
            "updated": 1699591648,
            "url": null
        }
    ],
    "has_more": false,
    "next_page": null,
    "url": "\/v1\/products\/search"
}
JSON;

    }

    private function getPricesForSetPrice()
    {
        return <<<'JSON'
{
    "object": "list",
    "data": [
        {
            "id": "price_1OAmdIK5kFGWTVZLdoYBToIf",
            "object": "price",
            "active": true,
            "billing_scheme": "per_unit",
            "created": 1699591672,
            "currency": "aud",
            "custom_unit_amount": null,
            "livemode": false,
            "lookup_key": null,
            "metadata": [],
            "nickname": "Description for event2",
            "product": "prod_Oyk7MJMkduPIDW",
            "recurring": null,
            "tax_behavior": "unspecified",
            "tiers_mode": null,
            "transform_quantity": null,
            "type": "one_time",
            "unit_amount": 1000,
            "unit_amount_decimal": "1000"
        },
        {
            "id": "price_1OAmdIK5kFGWTVZLRKwoEJZ8",
            "object": "price",
            "active": true,
            "billing_scheme": "per_unit",
            "created": 1699591672,
            "currency": "aud",
            "custom_unit_amount": null,
            "livemode": false,
            "lookup_key": null,
            "metadata": [],
            "nickname": null,
            "product": "prod_Oyk7MJMkduPIDW",
            "recurring": null,
            "tax_behavior": "unspecified",
            "tiers_mode": null,
            "transform_quantity": null,
            "type": "one_time",
            "unit_amount": 2000,
            "unit_amount_decimal": "2000"
        }
    ],
    "has_more": false,
    "url": "\/v1\/prices"
}
JSON;
    }

    private function getPricesForVariablePrice()
    {
        return <<<'JSON'
{
    "object": "list",
    "data": [
        {
            "id": "price_1OAmcuK5kFGWTVZLhVgqnJD8",
            "object": "price",
            "active": true,
            "billing_scheme": "per_unit",
            "created": 1699591648,
            "currency": "aud",
            "custom_unit_amount": null,
            "livemode": false,
            "lookup_key": null,
            "metadata": [],
            "nickname": null,
            "product": "prod_Oyk7eIZVR0Hwag",
            "recurring": {
                "aggregate_usage": null,
                "interval": "month",
                "interval_count": 1,
                "trial_period_days": null,
                "usage_type": "licensed"
            },
            "tax_behavior": "unspecified",
            "tiers_mode": null,
            "transform_quantity": null,
            "type": "recurring",
            "unit_amount": 500,
            "unit_amount_decimal": "500"
        }
    ],
    "has_more": false,
    "url": "\/v1\/prices"
}
JSON;

    }
}
