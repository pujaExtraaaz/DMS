<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'payment' => [
        'provider' => env('PAYMENT_PROVIDER', 'internal'),
        'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
    ],

    'meta' => [
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'access_token' => env('META_ACCESS_TOKEN'),
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN', env('META_ACCESS_TOKEN')),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],

    'tally' => [
        'endpoint' => env('TALLY_ENDPOINT'),
        'company' => env('TALLY_COMPANY'),
        'use_connector' => filter_var(env('TALLY_USE_CONNECTOR', false), FILTER_VALIDATE_BOOLEAN),
        'connector_token' => env('TALLY_CONNECTOR_TOKEN'),

        // Ledger / godown names must match the Tally company exactly (case-insensitive).
        'purchase_ledger' => env('TALLY_PURCHASE_LEDGER', 'Purchase'),
        'purchase_ledger_interstate' => env('TALLY_PURCHASE_LEDGER_INTERSTATE'),
        'sales_ledger' => env('TALLY_SALES_LEDGER', 'Sales'),
        'input_cgst_ledger' => env('TALLY_INPUT_CGST_LEDGER', 'CGST'),
        'input_sgst_ledger' => env('TALLY_INPUT_SGST_LEDGER', 'SGST'),
        'input_igst_ledger' => env('TALLY_INPUT_IGST_LEDGER', 'IGST'),
        'output_cgst_ledger' => env('TALLY_OUTPUT_CGST_LEDGER', 'CGST'),
        'output_sgst_ledger' => env('TALLY_OUTPUT_SGST_LEDGER', 'SGST'),
        'output_igst_ledger' => env('TALLY_OUTPUT_IGST_LEDGER', 'IGST'),
        'round_off_ledger' => env('TALLY_ROUND_OFF_LEDGER', 'Round Off'),
        'cash_ledger' => env('TALLY_CASH_LEDGER', 'Cash'),
        'bank_ledger' => env('TALLY_BANK_LEDGER', 'Bank'),
        'default_godown' => env('TALLY_DEFAULT_GODOWN', 'Main Location'),
        'use_batches' => filter_var(env('TALLY_USE_BATCHES', false), FILTER_VALIDATE_BOOLEAN),
    ],

    // Masters India GSP — powers real IRN + E-way generation.
    // Sandbox: https://sandb-api.mastersindia.co, Production: https://commonapi.mastersindia.co
    'mastersindia' => [
        'base_url' => env('MASTERSINDIA_BASE_URL', 'https://sandb-api.mastersindia.co'),
        'client_id' => env('MASTERSINDIA_CLIENT_ID'),
        'client_secret' => env('MASTERSINDIA_CLIENT_SECRET'),
        'username' => env('MASTERSINDIA_USERNAME'),
        'password' => env('MASTERSINDIA_PASSWORD'),
        'gstin' => env('MASTERSINDIA_GSTIN'),
        'timeout' => (int) env('MASTERSINDIA_TIMEOUT', 30),
        // When true and credentials are missing, service falls back to deterministic dummy IRN/EWB
        // so the flow keeps working in developer environments.
        'fallback_to_stub' => filter_var(env('MASTERSINDIA_FALLBACK_TO_STUB', true), FILTER_VALIDATE_BOOLEAN),
    ],

    // GSTIN verification & taxpayer lookup service (sandbox, cleartax, mastersindia, or custom)
    'gst' => [
        'provider' => env('GST_PROVIDER', 'sandbox'),
        'api_url' => env('GST_API_URL'),
        'api_key' => env('GST_API_KEY'),
        'api_secret' => env('GST_API_SECRET'),
        'timeout' => (int) env('GST_API_TIMEOUT', 15),
        'verify_ssl' => filter_var(env('GST_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],
];