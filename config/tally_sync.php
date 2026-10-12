<?php

/**
 * Dormant TallyPrime sync. Native Books is the live accounting path.
 * Re-enable later by setting TALLY_SYNC_ENABLED=true, registering the
 * auto-enqueue observer, and loading the connector public routes.
 */
return [
    /*
    | Native Books is the live accounting path. Keep this false until you
    | intentionally revive TallyPrime sync. Templates stay available either way.
    */
    'enabled' => (bool) env('TALLY_SYNC_ENABLED', false),

    /* When enabled, load /api/tally-connector/* for the office bridge. */
    'expose_connector_api' => (bool) env('TALLY_EXPOSE_CONNECTOR_API', true),

    /* When enabled, register TallyAutoEnqueueObserver on DMS documents. */
    'auto_enqueue' => (bool) env('TALLY_AUTO_ENQUEUE', true),

    'company' => env('TALLY_COMPANY', env('TALLY_COMPANY_NAME', '')),

    'endpoint' => env('TALLY_ENDPOINT', 'http://localhost:9000'),

    'use_connector' => (bool) env('TALLY_USE_CONNECTOR', true),

    'connector_token' => env('TALLY_CONNECTOR_TOKEN'),

    'default_godown' => env('TALLY_DEFAULT_GODOWN', 'Main Location'),

    'use_batches' => (bool) env('TALLY_USE_BATCHES', false),

    'ledgers' => [
        'sales' => env('TALLY_SALES_LEDGER', 'Sales'),
        'purchase' => env('TALLY_PURCHASE_LEDGER', 'Purchase'),
        'purchase_interstate' => env('TALLY_PURCHASE_LEDGER_INTERSTATE'),
        'cash' => env('TALLY_CASH_LEDGER', 'Cash'),
        'bank' => env('TALLY_BANK_LEDGER', 'Bank'),
        'round_off' => env('TALLY_ROUND_OFF_LEDGER', 'Round Off'),
        'output_cgst' => env('TALLY_OUTPUT_CGST_LEDGER', 'Output CGST'),
        'output_sgst' => env('TALLY_OUTPUT_SGST_LEDGER', 'Output SGST'),
        'output_igst' => env('TALLY_OUTPUT_IGST_LEDGER', 'Output IGST'),
        'input_cgst' => env('TALLY_INPUT_CGST_LEDGER', 'Input CGST'),
        'input_sgst' => env('TALLY_INPUT_SGST_LEDGER', 'Input SGST'),
        'input_igst' => env('TALLY_INPUT_IGST_LEDGER', 'Input IGST'),
    ],
];
