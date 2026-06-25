<?php

declare(strict_types=1);

use App\DTO\Stripe\StripeEvent;
use App\Handlers\Stripe\StripeEventRouter;
use App\Repositories\WebhookEventRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
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

expect()->extend('toBeOne', fn () => $this->toBe(1));

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

/**
 * @throws JsonException
 */
function getCheckoutSessionCompleted(): mixed
{
    $json = '{
    "object": {
        "id": "cs_test_a1EzAkmSvHsPe059ouFVO8umXpkf11Dh1MyBSg8oKEpsFDKJ76kGISR8Iq",
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
        "created": 1781801112,
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
        "expires_at": 1781887512,
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
        "payment_intent": "pi_3Tjj429x8obd1Gi600AWNrv7",
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
        "payment_method_types": ["card", "link", "amazon_pay"],
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

/**
 * @throws JsonException
 */
function getFromInvoicePaymentSucceeded(): mixed
{
    $json = '{
    "object": {
        "id": "in_1TiGpo9x8obd1Gi6xPCypqoq",
        "object": "invoice",
        "account_country": "DE",
        "account_name": "ai_pdf_summarize sandbox",
        "account_tax_ids": null,
        "amount_due": 2000,
        "amount_overpaid": 0,
        "amount_paid": 2000,
        "amount_remaining": 0,
        "amount_shipping": 0,
        "application": null,
        "attempt_count": 1,
        "attempted": true,
        "auto_advance": false,
        "automatic_tax": {
            "disabled_reason": null,
            "enabled": false,
            "liability": null,
            "provider": null,
            "status": null
        },
        "automatically_finalizes_at": null,
        "billing_reason": "manual",
        "collection_method": "charge_automatically",
        "created": 1781454272,
        "currency": "usd",
        "custom_fields": null,
        "customer": "cus_UhgCbT0bzNVea7",
        "customer_account": null,
        "customer_address": null,
        "customer_email": null,
        "customer_name": null,
        "customer_phone": null,
        "customer_shipping": null,
        "customer_tax_exempt": "none",
        "customer_tax_ids": [],
        "default_payment_method": null,
        "default_source": null,
        "default_tax_rates": [],
        "description": "(created by Stripe CLI)",
        "discounts": [],
        "due_date": null,
        "effective_at": 1781454273,
        "ending_balance": 0,
        "footer": null,
        "from_invoice": null,
        "hosted_invoice_url": "https://invoice.stripe.com/i/acct_1TcobX9x8obd1Gi6/test_YWNjdF8xVGNvYlg5eDhvYmQxR2k2LF9VaGdDTmNibXhHc0U2RzNZTDZIYmlOUjZBaDBSemM5LDE3MTk5NTA3Nw0200zIyEgy48?s=ap",
        "invoice_pdf": "https://pay.stripe.com/invoice/acct_1TcobX9x8obd1Gi6/test_YWNjdF8xVGNvYlg5eDhvYmQxR2k2LF9VaGdDTmNibXhHc0U2RzNZTDZIYmlOUjZBaDBSemM5LDE3MTk5NTA3Nw0200zIyEgy48/pdf?s=ap",
        "issuer": {
            "type": "self"
        },
        "last_finalization_error": null,
        "latest_revision": null,
        "lines": {
            "object": "list",
            "data": [
                {
                    "id": "il_1TiGpn9x8obd1Gi6zdpNXKrT",
                    "object": "line_item",
                    "amount": 2000,
                    "currency": "usd",
                    "description": "(created by Stripe CLI)",
                    "discount_amounts": [],
                    "discountable": true,
                    "discounts": [],
                    "invoice": "in_1TiGpo9x8obd1Gi6xPCypqoq",
                    "livemode": false,
                    "metadata": {},
                    "parent": {
                    "invoice_item_details": {
                        "invoice_item": "ii_1TiGpn9x8obd1Gi6TB99WxSm",
                            "proration": false,
                            "proration_details": {
                            "credited_items": null
                            },
                            "subscription": null
                        },
                        "subscription_item_details": null,
                        "type": "invoice_item_details"
                    },
                    "period": {
                    "end": 1781454271,
                        "start": 1781454271
                    },
                    "pretax_credit_amounts": [],
                    "pricing": {
                    "price_details": {
                        "price": "price_1TiGpn9x8obd1Gi6cUeMtcoK",
                            "product": "prod_UhgCzvrbTxrhBZ"
                        },
                        "type": "price_details",
                        "unit_amount_decimal": "2000"
                    },
                    "quantity": 1,
                    "quantity_decimal": "1",
                    "subtotal": 2000,
                    "taxes": []
                }
            ],
            "has_more": false,
            "total_count": 1,
            "url": "/v1/invoices/in_1TiGpo9x8obd1Gi6xPCypqoq/lines"
        },
        "livemode": false,
        "metadata": {},
        "next_payment_attempt": null,
        "number": "HT1J4WZS-0001",
        "on_behalf_of": null,
        "parent": null,
        "payment_settings": {
            "default_mandate": null,
            "payment_method_options": null,
            "payment_method_types": null
        },
        "period_end": 1781454272,
        "period_start": 1781454272,
        "post_payment_credit_notes_amount": 0,
        "pre_payment_credit_notes_amount": 0,
        "receipt_number": null,
        "rendering": {
            "amount_tax_display": null,
            "pdf": {
                "page_size": "letter"
            },
            "template": null,
            "template_version": null
        },
        "shipping_cost": null,
        "shipping_details": null,
        "starting_balance": 0,
        "statement_descriptor": null,
        "status": "paid",
        "status_transitions": {
            "finalized_at": 1781454273,
            "marked_uncollectible_at": null,
            "paid_at": 1781454273,
            "voided_at": null
        },
        "subtotal": 2000,
        "subtotal_excluding_tax": 2000,
        "test_clock": null,
        "total": 2000,
        "total_discount_amounts": [],
        "total_excluding_tax": 2000,
        "total_pretax_credit_amounts": [],
        "total_taxes": [],
        "webhooks_delivered_at": 1781454273
    },
    "previous_attributes": null
}';

    return json_decode($json, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);

}

function getFromInvoicePaymentFailed(): mixed
{
    $json = '{
    "object": {
        "id": "in_1TiaiI9x8obd1Gi6LjNCEUwt",
        "object": "invoice",
        "account_country": "DE",
        "account_name": "ai_pdf_summarize sandbox",
        "account_tax_ids": null,
        "amount_due": 2000,
        "amount_overpaid": 0,
        "amount_paid": 0,
        "amount_remaining": 2000,
        "amount_shipping": 0,
        "application": null,
        "attempt_count": 1,
        "attempted": true,
        "auto_advance": false,
        "automatic_tax": {
            "disabled_reason": null,
            "enabled": false,
            "liability": null,
            "provider": null,
            "status": null
        },
        "automatically_finalizes_at": null,
        "billing_reason": "manual",
        "collection_method": "charge_automatically",
        "created": 1781530686,
        "currency": "usd",
        "custom_fields": null,
        "customer": "cus_Ui0jNbMEQHQi7X",
        "customer_account": null,
        "customer_address": null,
        "customer_email": null,
        "customer_name": null,
        "customer_phone": null,
        "customer_shipping": null,
        "customer_tax_exempt": "none",
        "customer_tax_ids": [],
        "default_payment_method": null,
        "default_source": null,
        "default_tax_rates": [],
        "description": "(created by Stripe CLI)",
        "discounts": [],
        "due_date": null,
        "effective_at": 1781530687,
        "ending_balance": 0,
        "footer": null,
        "from_invoice": null,
        "hosted_invoice_url": "https://invoice.stripe.com/i/acct_1TcobX9x8obd1Gi6/test_YWNjdF8xVGNvYlg5eDhvYmQxR2k2LF9VaTBqQWZYemxBWUNORW14VVltTXlmeExqTlMxaGJ0LDE3MjA3MTQ5MA0200w2uWecXu?s=ap",
        "invoice_pdf": "https://pay.stripe.com/invoice/acct_1TcobX9x8obd1Gi6/test_YWNjdF8xVGNvYlg5eDhvYmQxR2k2LF9VaTBqQWZYemxBWUNORW14VVltTXlmeExqTlMxaGJ0LDE3MjA3MTQ5MA0200w2uWecXu/pdf?s=ap",
        "issuer": {
            "type": "self"
        },
        "last_finalization_error": null,
        "latest_revision": null,
        "lines": {
            "object": "list",
            "data": [
                {
                    "id": "il_1TiaiH9x8obd1Gi6fG6d0I4R",
                    "object": "line_item",
                    "amount": 2000,
                    "currency": "usd",
                    "description": "(created by Stripe CLI)",
                    "discount_amounts": [],
                    "discountable": true,
                    "discounts": [],
                    "invoice": "in_1TiaiI9x8obd1Gi6LjNCEUwt",
                    "livemode": false,
                    "metadata": {},
                    "parent": {
                        "invoice_item_details": {
                            "invoice_item": "ii_1TiaiH9x8obd1Gi6X3csQqhF",
                            "proration": false,
                            "proration_details": {
                                "credited_items": null
                            },
                            "subscription": null
                        },
                        "subscription_item_details": null,
                        "type": "invoice_item_details"
                    },
                    "period": {
                        "end": 1781530685,
                        "start": 1781530685
                    },
                    "pretax_credit_amounts": [],
                    "pricing": {
                        "price_details": {
                            "price": "price_1TiGpn9x8obd1Gi6cUeMtcoK",
                            "product": "prod_UhgCzvrbTxrhBZ"
                        },
                        "type": "price_details",
                        "unit_amount_decimal": "2000"
                    },
                    "quantity": 1,
                    "quantity_decimal": "1",
                    "subtotal": 2000,
                    "taxes": []
                }
            ],
            "has_more": false,
            "total_count": 1,
            "url": "/v1/invoices/in_1TiaiI9x8obd1Gi6LjNCEUwt/lines"
        },
        "livemode": false,
        "metadata": {},
        "next_payment_attempt": null,
        "number": "HT1J4WZS-0003",
        "on_behalf_of": null,
        "parent": null,
        "payment_settings": {
            "default_mandate": null,
            "payment_method_options": null,
            "payment_method_types": null
        },
        "period_end": 1781530686,
        "period_start": 1781530686,
        "post_payment_credit_notes_amount": 0,
        "pre_payment_credit_notes_amount": 0,
        "receipt_number": null,
        "rendering": {
            "amount_tax_display": null,
            "pdf": {
                "page_size": "letter"
            },
            "template": null,
            "template_version": null
        },
        "shipping_cost": null,
        "shipping_details": null,
        "starting_balance": 0,
        "statement_descriptor": null,
        "status": "open",
        "status_transitions": {
            "finalized_at": 1781530687,
            "marked_uncollectible_at": null,
            "paid_at": null,
            "voided_at": null
        },
        "subtotal": 2000,
        "subtotal_excluding_tax": 2000,
        "test_clock": null,
        "total": 2000,
        "total_discount_amounts": [],
        "total_excluding_tax": 2000,
        "total_pretax_credit_amounts": [],
        "total_taxes": [],
        "webhooks_delivered_at": 1781530687
    },
    "previous_attributes": null
}';

    return json_decode($json, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);

}

function getFromCustomerSubscriptionDeleted(): mixed
{
    $json = '{
    "object": {
        "id": "sub_1TiHOh9x8obd1Gi68WZu8qPV",
        "object": "subscription",
        "application": null,
        "application_fee_percent": null,
        "automatic_tax": {
            "disabled_reason": null,
            "enabled": false,
            "liability": null
        },
        "billing_cycle_anchor": 1781456435,
        "billing_cycle_anchor_config": null,
        "billing_mode": {
            "flexible": {
                "proration_discounts": "included"
            },
            "type": "flexible",
            "updated_at": 1781456435
        },
        "billing_thresholds": null,
        "cancel_at": null,
        "cancel_at_period_end": false,
        "canceled_at": 1781456440,
        "cancellation_details": {
            "comment": null,
            "feedback": null,
            "reason": "cancellation_requested"
        },
        "collection_method": "charge_automatically",
        "created": 1781456435,
        "currency": "usd",
        "customer": "cus_Uhgmxf1nOLk1OK",
        "customer_account": null,
        "days_until_due": null,
        "default_payment_method": null,
        "default_source": null,
        "default_tax_rates": [],
        "description": null,
        "discounts": [],
        "ended_at": 1781456440,
        "invoice_settings": {
            "account_tax_ids": null,
            "custom_fields": null,
            "description": null,
            "footer": null,
            "issuer": {
                "type": "self"
            }
        },
        "items": {
            "object": "list",
            "data": [
                {
                    "id": "si_UhgmprqJ8yPLOW",
                    "object": "subscription_item",
                    "billing_thresholds": null,
                    "created": 1781456436,
                    "current_period_end": 1784048435,
                    "current_period_start": 1781456435,
                    "discounts": [],
                    "metadata": {},
                    "plan": {
                        "id": "price_1TiHOf9x8obd1Gi6u35xMe3Q",
                        "object": "plan",
                        "active": true,
                        "amount": 1500,
                        "amount_decimal": "1500",
                        "billing_scheme": "per_unit",
                        "created": 1781456433,
                        "currency": "usd",
                        "interval": "month",
                        "interval_count": 1,
                        "livemode": false,
                        "metadata": {},
                        "meter": null,
                        "nickname": null,
                        "product": "prod_Uhgmb3ycwc0jml",
                        "tiers_mode": null,
                        "transform_usage": null,
                        "trial_period_days": null,
                        "usage_type": "licensed"
                    },
                    "price": {
                        "id": "price_1TiHOf9x8obd1Gi6u35xMe3Q",
                        "object": "price",
                        "active": true,
                        "billing_scheme": "per_unit",
                        "created": 1781456433,
                        "currency": "usd",
                        "custom_unit_amount": null,
                        "livemode": false,
                        "lookup_key": null,
                        "metadata": {},
                        "nickname": null,
                        "product": "prod_Uhgmb3ycwc0jml",
                        "recurring": {
                            "interval": "month",
                            "interval_count": 1,
                            "meter": null,
                            "trial_period_days": null,
                            "usage_type": "licensed"
                        },
                        "tax_behavior": "unspecified",
                        "tiers_mode": null,
                        "transform_quantity": null,
                        "type": "recurring",
                        "unit_amount": 1500,
                        "unit_amount_decimal": "1500"
                    },
                    "quantity": 1,
                    "subscription": "sub_1TiHOh9x8obd1Gi68WZu8qPV",
                    "tax_rates": []
                }
            ],
            "has_more": false,
            "total_count": 1,
            "url": "/v1/subscription_items?subscription=sub_1TiHOh9x8obd1Gi68WZu8qPV"
        },
        "latest_invoice": "in_1TiHOi9x8obd1Gi6lJBFyb0I",
        "livemode": false,
        "managed_payments": {
            "enabled": false
        },
        "metadata": {},
        "next_pending_invoice_item_invoice": null,
        "on_behalf_of": null,
        "pause_collection": null,
        "payment_settings": {
            "payment_method_options": null,
            "payment_method_types": null,
            "save_default_payment_method": "off"
        },
        "pending_invoice_item_interval": null,
        "pending_setup_intent": null,
        "pending_update": null,
        "plan": {
            "id": "price_1TiHOf9x8obd1Gi6u35xMe3Q",
            "object": "plan",
            "active": true,
            "amount": 1500,
            "amount_decimal": "1500",
            "billing_scheme": "per_unit",
            "created": 1781456433,
            "currency": "usd",
            "interval": "month",
            "interval_count": 1,
            "livemode": false,
            "metadata": {},
            "meter": null,
            "nickname": null,
            "product": "prod_Uhgmb3ycwc0jml",
            "tiers_mode": null,
            "transform_usage": null,
            "trial_period_days": null,
            "usage_type": "licensed"
        },
        "quantity": 1,
        "schedule": null,
        "start_date": 1781456435,
        "status": "canceled",
        "test_clock": null,
        "transfer_data": null,
        "trial_end": null,
        "trial_settings": {
            "end_behavior": {
                "missing_payment_method": "create_invoice"
            }
        },
        "trial_start": null
    },
    "previous_attributes": null
}';

    return json_decode($json, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
}

function getFromCustomerSubscriptionCreated(): mixed
{
    $json = '{
    "object": {
        "id": "sub_1Tiapa9x8obd1Gi6eaLWdW1q",
        "object": "subscription",
        "application": null,
        "application_fee_percent": null,
        "automatic_tax": {
            "disabled_reason": null,
            "enabled": false,
            "liability": null
        },
        "billing_cycle_anchor": 1781531138,
        "billing_cycle_anchor_config": null,
        "billing_mode": {
            "flexible": {
                "proration_discounts": "included"
            },
            "type": "flexible",
            "updated_at": 1781531138
        },
        "billing_thresholds": null,
        "cancel_at": null,
        "cancel_at_period_end": false,
        "canceled_at": null,
        "cancellation_details": {
            "comment": null,
            "feedback": null,
            "reason": null
        },
        "collection_method": "charge_automatically",
        "created": 1781531138,
        "currency": "usd",
        "customer": "cus_Ui0rCTaDk3JR7n",
        "customer_account": null,
        "days_until_due": null,
        "default_payment_method": null,
        "default_source": null,
        "default_tax_rates": [],
        "description": null,
        "discounts": [],
        "ended_at": null,
        "invoice_settings": {
            "account_tax_ids": null,
            "custom_fields": null,
            "description": null,
            "footer": null,
            "issuer": {
                "type": "self"
            }
        },
        "items": {
            "object": "list",
            "data": [
                {
                    "id": "si_Ui0rMBXs5Epm3v",
                    "object": "subscription_item",
                    "billing_thresholds": null,
                    "created": 1781531139,
                    "current_period_end": 1784123138,
                    "current_period_start": 1781531138,
                    "discounts": [],
                    "metadata": {},
                    "plan": {
                        "id": "price_1Tiapa9x8obd1Gi6LQQxP7jj",
                        "object": "plan",
                        "active": true,
                        "amount": 1500,
                        "amount_decimal": "1500",
                        "billing_scheme": "per_unit",
                        "created": 1781531138,
                        "currency": "usd",
                        "interval": "month",
                        "interval_count": 1,
                        "livemode": false,
                        "metadata": {},
                        "meter": null,
                        "nickname": null,
                        "product": "prod_Ui0rKO0Xi1tVuU",
                        "tiers_mode": null,
                        "transform_usage": null,
                        "trial_period_days": null,
                        "usage_type": "licensed"
                    },
                    "price": {
                        "id": "price_1Tiapa9x8obd1Gi6LQQxP7jj",
                        "object": "price",
                        "active": true,
                        "billing_scheme": "per_unit",
                        "created": 1781531138,
                        "currency": "usd",
                        "custom_unit_amount": null,
                        "livemode": false,
                        "lookup_key": null,
                        "metadata": {},
                        "nickname": null,
                        "product": "prod_Ui0rKO0Xi1tVuU",
                        "recurring": {
                            "interval": "month",
                            "interval_count": 1,
                            "meter": null,
                            "trial_period_days": null,
                            "usage_type": "licensed"
                        },
                        "tax_behavior": "unspecified",
                        "tiers_mode": null,
                        "transform_quantity": null,
                        "type": "recurring",
                        "unit_amount": 1500,
                        "unit_amount_decimal": "1500"
                    },
                    "quantity": 1,
                    "subscription": "sub_1Tiapa9x8obd1Gi6eaLWdW1q",
                    "tax_rates": []
                }
            ],
            "has_more": false,
            "total_count": 1,
            "url": "/v1/subscription_items?subscription=sub_1Tiapa9x8obd1Gi6eaLWdW1q"
        },
        "latest_invoice": "in_1Tiapb9x8obd1Gi6OKhikjJs",
        "livemode": false,
        "managed_payments": {
            "enabled": false
        },
        "metadata": {},
        "next_pending_invoice_item_invoice": null,
        "on_behalf_of": null,
        "pause_collection": null,
        "payment_settings": {
            "payment_method_options": null,
            "payment_method_types": null,
            "save_default_payment_method": "off"
        },
        "pending_invoice_item_interval": null,
        "pending_setup_intent": null,
        "pending_update": null,
        "plan": {
            "id": "price_1Tiapa9x8obd1Gi6LQQxP7jj",
            "object": "plan",
            "active": true,
            "amount": 1500,
            "amount_decimal": "1500",
            "billing_scheme": "per_unit",
            "created": 1781531138,
            "currency": "usd",
            "interval": "month",
            "interval_count": 1,
            "livemode": false,
            "metadata": {},
            "meter": null,
            "nickname": null,
            "product": "prod_Ui0rKO0Xi1tVuU",
            "tiers_mode": null,
            "transform_usage": null,
            "trial_period_days": null,
            "usage_type": "licensed"
        },
        "quantity": 1,
        "schedule": null,
        "start_date": 1781531138,
        "status": "active",
        "test_clock": null,
        "transfer_data": null,
        "trial_end": null,
        "trial_settings": {
            "end_behavior": {
                "missing_payment_method": "create_invoice"
            }
        },
        "trial_start": null
    },
    "previous_attributes": null
}';

    return json_decode($json, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
}

function getFromCustomerSubscriptionUpdated(): mixed
{
    $json = '{
    "object": {
        "id": "sub_1Tiapa9x8obd1Gi6eaLWdW1q",
        "object": "subscription",
        "application": null,
        "application_fee_percent": null,
        "automatic_tax": {
            "disabled_reason": null,
            "enabled": false,
            "liability": null
        },
        "billing_cycle_anchor": 1781531138,
        "billing_cycle_anchor_config": null,
        "billing_mode": {
            "flexible": {
                "proration_discounts": "included"
            },
            "type": "flexible",
            "updated_at": 1781531138
        },
        "billing_thresholds": null,
        "cancel_at": null,
        "cancel_at_period_end": false,
        "canceled_at": null,
        "cancellation_details": {
            "comment": null,
            "feedback": null,
            "reason": null
        },
        "collection_method": "charge_automatically",
        "created": 1781531138,
        "currency": "usd",
        "customer": "cus_Ui0rCTaDk3JR7n",
        "customer_account": null,
        "days_until_due": null,
        "default_payment_method": null,
        "default_source": null,
        "default_tax_rates": [],
        "description": null,
        "discounts": [],
        "ended_at": null,
        "invoice_settings": {
            "account_tax_ids": null,
            "custom_fields": null,
            "description": null,
            "footer": null,
            "issuer": {
                "type": "self"
            }
        },
        "items": {
            "object": "list",
            "data": [
                {
                    "id": "si_Ui0rMBXs5Epm3v",
                    "object": "subscription_item",
                    "billing_thresholds": null,
                    "created": 1781531139,
                    "current_period_end": 1784123138,
                    "current_period_start": 1781531138,
                    "discounts": [],
                    "metadata": {},
                    "plan": {
                        "id": "price_1Tiapa9x8obd1Gi6LQQxP7jj",
                        "object": "plan",
                        "active": true,
                        "amount": 1500,
                        "amount_decimal": "1500",
                        "billing_scheme": "per_unit",
                        "created": 1781531138,
                        "currency": "usd",
                        "interval": "month",
                        "interval_count": 1,
                        "livemode": false,
                        "metadata": {},
                        "meter": null,
                        "nickname": null,
                        "product": "prod_Ui0rKO0Xi1tVuU",
                        "tiers_mode": null,
                        "transform_usage": null,
                        "trial_period_days": null,
                        "usage_type": "licensed"
                    },
                    "price": {
                        "id": "price_1Tiapa9x8obd1Gi6LQQxP7jj",
                        "object": "price",
                        "active": true,
                        "billing_scheme": "per_unit",
                        "created": 1781531138,
                        "currency": "usd",
                        "custom_unit_amount": null,
                        "livemode": false,
                        "lookup_key": null,
                        "metadata": {},
                        "nickname": null,
                        "product": "prod_Ui0rKO0Xi1tVuU",
                        "recurring": {
                            "interval": "month",
                            "interval_count": 1,
                            "meter": null,
                            "trial_period_days": null,
                            "usage_type": "licensed"
                        },
                        "tax_behavior": "unspecified",
                        "tiers_mode": null,
                        "transform_quantity": null,
                        "type": "recurring",
                        "unit_amount": 1500,
                        "unit_amount_decimal": "1500"
                    },
                    "quantity": 1,
                    "subscription": "sub_1Tiapa9x8obd1Gi6eaLWdW1q",
                    "tax_rates": []
                }
            ],
            "has_more": false,
            "total_count": 1,
            "url": "/v1/subscription_items?subscription=sub_1Tiapa9x8obd1Gi6eaLWdW1q"
        },
        "latest_invoice": "in_1Tiapb9x8obd1Gi6OKhikjJs",
        "livemode": false,
        "managed_payments": {
            "enabled": false
        },
        "metadata": {
            "foo": "bar"
        },
        "next_pending_invoice_item_invoice": null,
        "on_behalf_of": null,
        "pause_collection": null,
        "payment_settings": {
            "payment_method_options": null,
            "payment_method_types": null,
            "save_default_payment_method": "off"
        },
        "pending_invoice_item_interval": null,
        "pending_setup_intent": null,
        "pending_update": null,
        "plan": {
            "id": "price_1Tiapa9x8obd1Gi6LQQxP7jj",
            "object": "plan",
            "active": true,
            "amount": 1500,
            "amount_decimal": "1500",
            "billing_scheme": "per_unit",
            "created": 1781531138,
            "currency": "usd",
            "interval": "month",
            "interval_count": 1,
            "livemode": false,
            "metadata": {},
            "meter": null,
            "nickname": null,
            "product": "prod_Ui0rKO0Xi1tVuU",
            "tiers_mode": null,
            "transform_usage": null,
            "trial_period_days": null,
            "usage_type": "licensed"
        },
        "quantity": 1,
        "schedule": null,
        "start_date": 1781531138,
        "status": "active",
        "test_clock": null,
        "transfer_data": null,
        "trial_end": null,
        "trial_settings": {
            "end_behavior": {
                "missing_payment_method": "create_invoice"
            }
        },
        "trial_start": null
    },
    "previous_attributes": {
        "metadata": {
            "foo": null
        }
    }
}';

    return json_decode($json, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
}

function makeMockStripeEvent(string $type): StripeEvent
{
    return new StripeEvent(
        'test_123',
        $type,
        ['customer' => 'cus_123'],
        ['user_id' => 1, 'plan_id' => 2]
    );
}

function makeRouterWithMockedHandler(MockInterface $handler): StripeEventRouter
{
    return new StripeEventRouter(
        [$handler],
        app()->make(WebhookEventRepository::class)
    );
}
