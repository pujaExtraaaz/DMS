<?php

/**
 * GST E-Invoice (IRN) integration — aligned with Masters India GSP / NIC contracts.
 * Generation is never automatic on invoice save; callers must trigger explicitly.
 */
return [
    'enabled' => filter_var(env('EINVOICE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // masters_india (default) | nic (reserved — not implemented yet)
    'provider' => env('EINVOICE_PROVIDER', 'masters_india'),

    // When true, dispatch GenerateSaleEinvoiceJob instead of sync HTTP
    'queue' => filter_var(env('EINVOICE_QUEUE', false), FILTER_VALIDATE_BOOLEAN),

    // Clear stuck "processing" after this many minutes
    'processing_stale_minutes' => (int) env('EINVOICE_PROCESSING_STALE_MINUTES', 15),
];
