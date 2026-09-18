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

];
