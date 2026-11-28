<?php

/**
 * GST E-Way Bill integration — aligned with Masters India GSP / NIC contracts.
 * Generation is never automatic on invoice save; callers must trigger explicitly.
 */
return [
    'enabled' => filter_var(env('EWAYBILL_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // masters_india (default) | nic (reserved — not implemented yet)
    'provider' => env('EWAY_BILL_PROVIDER', env('EWAYBILL_PROVIDER', 'masters_india')),

    'queue' => filter_var(env('EWAYBILL_QUEUE', false), FILTER_VALIDATE_BOOLEAN),

    'processing_stale_minutes' => (int) env('EWAYBILL_PROCESSING_STALE_MINUTES', 15),

    /**
     * When true, distance_km = 0 is allowed and sent to the portal so pin-pair
     * distance is calculated officially (avoids NIC/GSP error 702 overshoots).
     */
    'auto_distance' => filter_var(env('EWAY_AUTO_DISTANCE', true), FILTER_VALIDATE_BOOLEAN),

    'pin_distance_estimate' => filter_var(env('EWAY_PIN_DISTANCE_ESTIMATE', true), FILTER_VALIDATE_BOOLEAN),
];
