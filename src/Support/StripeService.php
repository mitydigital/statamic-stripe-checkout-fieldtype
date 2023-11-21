<?php

namespace MityDigital\StatamicStripeCheckoutFieldtype\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use MityDigital\StatamicStripeCheckoutFieldtype\Exceptions\FormBlueprintMissingStripeCheckoutException;
use MityDigital\StatamicStripeCheckoutFieldtype\Exceptions\NoLineItemsException;
use MityDigital\StatamicStripeCheckoutFieldtype\Facades\StripeCheckoutFieldtype as StripeCheckoutFieldtypeFacade;
use Statamic\Facades\Data;
use Statamic\Facades\URL;
use Statamic\Fields\Field;
use Statamic\Forms\Submission;
use Statamic\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    const STRIPE_VERSION = '2023-10-16';

    const STRIPE_EVENTS = [
        'plan.created',
        'plan.deleted',
        'plan.updated',
        'price.created',
        'price.deleted',
        'price.updated',
        'product.created',
        'product.deleted',
        'product.updated',
    ];

    protected StripeClient $service;

    public function createCheckoutSession(Submission $submission): bool|string
    {
        $this->getService();

        try {
            $payload = $this->buildCheckoutPayloadFromSubmission($submission);

            // create the checkout session
            $checkout = $this->service->checkout->sessions->create($payload);

            // save the id with the submission
            $handle = StripeCheckoutFieldtypeFacade::getStripeCheckoutFieldHandle($submission->form());
            $value = $submission->data()[$handle];

            if (! is_array($value)) {
                $value = [
                    'value' => $payload['mode'],
                ];
            }
            $value['checkout_session_id'] = $checkout['id'];
            $submission->set($handle, $value)->saveQuietly();

            // save the session id
            Session::put(StripeCheckoutFieldtypeFacade::getSubmissionSessionKey($checkout['id']), $submission->id());

            return $checkout['url'];
        } catch (NoLineItemsException $e) {
            Log::error('StatamicStripeCheckout createCheckoutSession No Line Items: '.$e->getMessage());

            return false;
        } catch (ApiErrorException $e) {
            Log::error('StatamicStripeCheckout createCheckoutSession StripeService API Error: '.$e->getMessage());

            return false;
        }
    }

    protected function getService(): StripeClient
    {
        if (! isset($this->service)) {
            $this->service = new StripeClient([
                'api_key' => StripeCheckoutFieldtypeFacade::getSecret(),
                'stripe_version' => StripeService::STRIPE_VERSION,
            ]);
        }

        return $this->service;
    }

    protected function buildCheckoutPayloadFromSubmission(Submission $submission): array
    {
        // load config from the form
        $config = $submission
            ->form()
            ->blueprint()
            ->fields()
            ->all()
            ->filter(fn (Field $field) => $field->type() === 'stripe_checkout')
            ->first();

        // if there is no config, then there's no fieldtype used
        if (! $config) {
            throw new FormBlueprintMissingStripeCheckoutException();
        }

        // get the data
        $data = $submission->data();

        $mode = $data->get($config->handle());
        if (is_array($mode) && array_key_exists('value', $mode)) {
            $mode = $mode['value'];
        }

        // build the payload
        $payload = [
            'client_reference_id' => $submission->id(),
            'mode' => $config->get('mode_choice') === 'yes' ? $mode : $config->get('mode'),
            'success_url' => $this->getUrl(
                $config->get('success_url'),
                $config->get('success_url_include_session', 'no') === 'yes'
            ),
            'currency' => $config->get('currency_code'),
            'metadata' => [
                'submission' => $submission->id(),
            ],
        ];

        // can only be used in "payment"
        if ($payload['mode'] === 'payment') {
            $payload['customer_creation'] = $config->get('customer_creation');
        }

        // line_items
        $lineItems = [];

        // prices
        // handle is a quantity
        foreach ($config->get('prices', []) as $price) {
            $quantity = $data->get($price['handle']);
            if (is_numeric($quantity) && $quantity > 0) {
                $lineItems[] = [
                    'price_id' => $price['price_id'],
                    'quantity' => $quantity,
                ];
            }
        }

        // products
        // handle is a value, quantity always 1
        foreach ($config->get('products', []) as $product) {
            $value = $data->get($product['handle']);
            if (is_numeric($value) && $value > 0) {
                $lineItem = [
                    'price_data' => [
                        'currency' => 'AUD',
                        'product' => $product['product_id'],
                        'unit_amount' => $value * 100,
                    ],
                    'quantity' => 1,
                ];

                // if subscription, set price up to be recurring monthly
                if ($payload['mode'] === 'subscription') {
                    $lineItem['price_data']['recurring'] = [
                        'interval' => $config->get('recurring_interval', 'month'),
                        'interval_count' => $config->get('recurring_interval_count', 1),
                    ];
                }

                $lineItems[] = $lineItem;
            }
        }

        $payload['line_items'] = $lineItems;

        if (empty($lineItems)) {
            throw new NoLineItemsException();
        }

        // allow_promotion_codes
        if ($config->get('allow_promotion_codes') === 'yes') {
            $payload['allow_promotion_codes'] = true;
        }

        // cancel_url
        if ($config->get('cancel_url')) {
            $payload['cancel_url'] = $this->getUrl($config->get('cancel_url'));
        }

        // customer_email
        if ($config->get('customer_email')) {
            // get the email value
            if ($emailAddress = $data->get($config->get('customer_email'))) {
                $payload['customer_email'] = $emailAddress;
            }
        }

        return $payload;
    }

    protected function getUrl($url, $includeSessionInUrl = false)
    {
        if (Str::contains($url, '::')) {
            $target = Data::find($url);

            $url = $target?->url();

            if ($includeSessionInUrl) {
                $url .= '?session_id={CHECKOUT_SESSION_ID}';
            }

            return URL::makeAbsolute($url);
        }

        if ($includeSessionInUrl) {
            if (str_contains($url, '?')) {
                $url .= '&session_id={CHECKOUT_SESSION_ID}';
            } else {
                $url .= '?session_id={CHECKOUT_SESSION_ID}';
            }
        }

        return $url;
    }

    public function createWebhook(bool $disabled = false): bool|string
    {
        // init the service
        $this->getService();

        try {
            // register the webhook
            $webhook = $this->service->webhookEndpoints->create([
                'api_version' => StripeService::STRIPE_VERSION,
                'enabled_events' => StripeService::STRIPE_EVENTS,
                'url' => route('stripe-checkout-fieldtype.webhook'),
            ]);

            // if disabled, update it to disable it
            if ($disabled) {
                $this->service->webhookEndpoints->update($webhook->id, [
                    'disabled' => true,
                ]);
            }

            return true;
        } catch (ApiErrorException $e) {
            Log::error('StatamicStripeCheckout createWebhook StripeService API Error: '.$e->getMessage());

            return $e->getMessage();
        }
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheProductsKey());
    }

    protected function cacheProductsKey(): string
    {
        return 'statamic_stripe_checkout_fieldtype_products';
    }

    public function getProducts(): array|Collection
    {
        return Cache::rememberForever($this->cacheProductsKey(), function () {

            // init the service
            $this->getService();

            // get the products
            $products = [];

            $hasMore = true;
            $nextPage = null;

            while ($hasMore) {
                $params = [
                    'query' => "active:'true'",
                    'limit' => 50,
                ];

                if ($nextPage) {
                    $params['page'] = $nextPage;
                }

                // do the call
                $data = $this->service
                    ->products
                    ->search($params)
                    ->toArray();

                // update products
                $products = array_merge($products, $data['data']);

                // do we have more?
                $hasMore = $data['has_more'];
                if ($hasMore) {
                    $nextPage = $data['next_page'];
                }
            }

            return collect($products)
                ->map(function ($product) {
                    // is this a website product?

                    // we will store prices here
                    $prices = [];

                    // used for looping
                    $hasMore = true;
                    $startingAfter = null;

                    while ($hasMore) {
                        // do the load
                        $params = [
                            'active' => true,
                            'product' => $product['id'],
                            'limit' => 50,
                        ];

                        if ($startingAfter) {
                            $params['starting_after'] = $startingAfter;
                        }

                        $data = $this->service->prices->all($params)->toArray();

                        // update prices
                        $prices = array_merge($prices, $data['data']);

                        // do we have more?
                        $hasMore = $data['has_more'];
                        if ($hasMore) {
                            $startingAfter = end($data['data'])['id'];
                        }
                    }

                    $prices = collect($prices)
                        ->map(function ($price) {
                            // if not wanted, return
                            if (array_key_exists('metadata', $price) &&
                                array_key_exists('website', $price['metadata']) &&
                                $price['metadata']['website'] == 'false') {
                                return false;
                            }

                            $token = ' ';
                            if (str_contains($price['nickname'], ':')) {
                                $token = ':';
                            }

                            $name = explode($token, $price['nickname'], 2);

                            return [
                                'id' => $price['id'],
                                'name' => $price['nickname'],
                                'type' => $price['type'],
                                'unit_amount' => $price['unit_amount'],
                                'amount' => $price['unit_amount'] / 100,
                            ];
                        });

                    // return the product
                    return [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'prices' => $prices,
                    ];

                })->sortBy([
                    ['name', 'asc'],
                ])
                ->values();
        });
    }
}
