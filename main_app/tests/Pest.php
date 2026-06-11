<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn() => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}

/**
 * @throws JsonException
 */
function getStripePayloadExample(): array
{
    $json = '{
  "object": {
    "id": "cs_test_a154wuuQibPymc5xrQnC8JQ0S57LczQrI2vb1msWg0XFuAotzJl3PtoLmM",
    "object": "checkout.session",
    "adaptive_pricing": {
      "enabled": true
    },
    "after_expiration": null,
    "allow_promotion_codes": null,
    "amount_subtotal": 3000,
    "amount_total": 3000,
    "automatic_tax": {
      "enabled": false,
      "liability": null,
      "provider": null,
      "status": null
    },
    "billing_address_collection": null,
    "branding_settings": {
      "background_color": "#ffffff",
      "border_style": "rounded",
      "button_color": "#0074d4",
      "display_name": "ai_pdf_summarize sandbox",
      "font_family": "default",
      "icon": null,
      "logo": null
    },
    "cancel_url": "https://httpbin.org/post",
    "client_reference_id": null,
    "client_secret": null,
    "collected_information": null,
    "consent": null,
    "consent_collection": null,
    "created": 1780858506,
    "currency": "usd",
    "currency_conversion": null,
    "custom_fields": [],
    "custom_text": {
      "after_submit": null,
      "shipping_address": null,
      "submit": null,
      "terms_of_service_acceptance": null
    },
    "customer": null,
    "customer_account": null,
    "customer_creation": "if_required",
    "customer_details": {
      "address": {
        "city": "South San Francisco",
        "country": "US",
        "line1": "354 Oyster Point Blvd",
        "line2": null,
        "postal_code": "94080",
        "state": "CA"
      },
      "business_name": null,
      "email": "stripe@example.com",
      "individual_name": null,
      "name": "Jenny Rosen",
      "phone": null,
      "tax_exempt": "none",
      "tax_ids": []
    },
    "customer_email": null,
    "discounts": [],
    "expires_at": 1780944906,
    "integration_identifier": null,
    "invoice": null,
    "invoice_creation": {
      "enabled": false,
      "invoice_data": {
        "account_tax_ids": null,
        "custom_fields": null,
        "description": null,
        "footer": null,
        "issuer": null,
        "metadata": {},
        "rendering_options": null
      }
    },
    "livemode": false,
    "locale": null,
    "managed_payments": {
      "enabled": false
    },
    "metadata": {},
    "mode": "payment",
    "origin_context": null,
    "payment_intent": "pi_3Tflqk9x8obd1Gi61RygPAZD",
    "payment_link": null,
    "payment_method_collection": "if_required",
    "payment_method_configuration_details": {
      "id": "pmc_1Tcoc39x8obd1Gi6I9nK8tsn",
      "parent": null
    },
    "payment_method_options": {
      "card": {
        "request_three_d_secure": "automatic"
      }
    },
    "payment_method_types": [
      "card",
      "link",
      "amazon_pay"
    ],
    "payment_status": "paid",
    "permissions": null,
    "phone_number_collection": {
      "enabled": false
    },
    "recovered_from": null,
    "saved_payment_method_options": null,
    "setup_intent": null,
    "shipping_address_collection": null,
    "shipping_cost": null,
    "shipping_options": [],
    "status": "complete",
    "submit_type": null,
    "subscription": null,
    "success_url": "https://httpbin.org/post",
    "total_details": {
      "amount_discount": 0,
      "amount_shipping": 0,
      "amount_tax": 0
    },
    "ui_mode": "hosted_page",
    "url": null,
    "wallet_options": null
  },
  "previous_attributes": null
}';
    return json_decode($json, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
}
