<?php

return [
    'essl' => [
        'enabled' => (bool) env('ESSL_ENABLED', false),
        'url' => env('ESSL_URL', ''),
        'token' => env('ESSL_TOKEN', env('TALLY_CONNECTOR_TOKEN', '')),
        'company' => env('ESSL_COMPANY', ''),
        'poll_minutes' => (int) env('ESSL_POLL_MINUTES', 15),
    ],

    /*
    | Configurable India-style statutory deductions applied during payroll
    | when structure lines do not already set the same pay-head code.
    */
    'statutory' => [
        'pf' => [
            'enabled' => (bool) env('HRMS_PF_ENABLED', true),
            'employee_rate' => (float) env('HRMS_PF_RATE', 12), // % of PF wage
            'wage_ceiling' => (float) env('HRMS_PF_CEILING', 15000),
            'base' => 'basic', // basic | basic_da | gross
        ],
        'esi' => [
            'enabled' => (bool) env('HRMS_ESI_ENABLED', true),
            'employee_rate' => (float) env('HRMS_ESI_RATE', 0.75),
            'gross_ceiling' => (float) env('HRMS_ESI_GROSS_CEILING', 21000),
        ],
        'pt' => [
            // Simple monthly PT slab (Maharashtra-style default; override via env/config)
            'enabled' => (bool) env('HRMS_PT_ENABLED', true),
            'slabs' => [
                ['upto' => 7500, 'amount' => 0],
                ['upto' => 10000, 'amount' => 175],
                ['upto' => null, 'amount' => 200], // February often 300 in MH; keep flat for v1
            ],
        ],
        'tds' => [
            // Flat provisional TDS % on taxable (gross - pf - standard deduction stub)
            'enabled' => (bool) env('HRMS_TDS_ENABLED', false),
            'rate' => (float) env('HRMS_TDS_RATE', 0),
            'standard_deduction_monthly' => (float) env('HRMS_TDS_STD_DED', 0),
        ],
    ],

    'comp_off' => [
        'ot_minutes_per_day' => (int) env('HRMS_COFF_OT_MINUTES', 480), // 8h OT = 1 COFF day
        'credit_holiday_work' => (bool) env('HRMS_COFF_HOLIDAY_WORK', true),
        'leave_type_code' => 'COFF',
    ],

    'rotational_off' => [
        // When weekly_off_mode=rotational and weekly_offs=[N], off every Nth week starting effective_from
        // week_index 0 = first week containing effective_from
    ],
];
