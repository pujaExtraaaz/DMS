<?php

namespace App\Support;

class NameCase
{
    /**
     * Fields that explicitly represent names and human-readable text and must always be formatted in Title Case.
     */
    protected static array $explicitFields = [
        'name',
        'legal_name',
        'trade_name',
        'company_name',
        'transporter_name',
        'driver_name',
        'customer_name',
        'supplier_name',
        'party_name',
        'contact_person',
        'contact_name',
        'first_name',
        'last_name',
        'middle_name',
        'site_name',
        'bank_name',
        'branch_name',
        'bank_branch',
        'branch',
        'account_holder_name',
        'shipping_name',
        'beneficiary_name',
        'actor_name',
        'approver_name',
        'created_by_name',
        'updated_by_name',
        'approved_by_name',
        'converted_by_name',
        'cancelled_by_name',
        'salesperson_name',
        'kpi_name',
        'form_name',
        'tally_name',
        'batch_name',
        'group_name',
        'concern_name',
        'brand_name',
        'category_name',
        'subcategory_name',
        'product_name',
        'product_type',
        'product_type_name',
        'classification',
        'customer_type',
        'company_type',
        'department',
        'designation',
        'leave_type',
        'expense_type',
        'relationship_type',
        'compliance_status',
        'nature_of_business',
        'headquarters',
        'industry',
        'address',
        'address_line_1',
        'address_line_2',
        'street',
        'landmark',
        'area',
        'locality',
        'city',
        'state',
        'country',
        'district',
        'taluka',
        'zone',
        'region',
        'location',
        'billing_address',
        'shipping_address',
        'label',
        'address_label',
        'owner_label',
        'period_label',
        'title',
        'beneficiary',
        'mailing_name',
        'name_on_receipt',
        'counterparty',
        'verified_by',
        'tag',
        'color_variant',
        'model',
        'brand',
        'category',
        'subcategory',
        'topic',
        'subject',
        'status_note',
    ];

    /**
     * Technical, identifier, sensitive, enum, and calculation fields that must NOT be converted to Title Case.
     */
    protected static array $excludedFields = [
        'username',
        'user_name',
        'file_name',
        'filename',
        'route_name',
        'permission_name',
        'guard_name',
        'class_name',
        'component_name',
        'table_name',
        'database_name',
        'disk_name',
        'column_name',
        'hostname',
        'domain_name',
        'currency_formal_name',
        'email',
        'secondary_email',
        'contact_email',
        'password',
        'password_confirmation',
        'current_password',
        'gstin',
        'supplier_gstin',
        'customer_gstin',
        'pan',
        'cin',
        'tan',
        'ifsc',
        'bank_ifsc',
        'ifsc_code',
        'beneficiary_ifsc',
        'hsn',
        'hsn_code',
        'sku',
        'part_number',
        'serial_no',
        'serial_number',
        'serial_numbers',
        'barcode',
        'barcode_scan',
        'bsr_code',
        'swift_code',
        'client_code',
        'udyam_registration_no',
        'msme_registration_no',
        'iec_code',
        'incorporation_no',
        'contract_number',
        'certificate_number',
        'code',
        'asset_code',
        'section_code',
        'account_number',
        'account_no',
        'bank_account_no',
        'beneficiary_account',
        'invoice_no',
        'invoice_number',
        'supplier_invoice_number',
        'order_no',
        'po_no',
        'pi_no',
        'lr_no',
        'challan_no',
        'challan_number',
        'slip_number',
        'voucher_number',
        'voucher_no',
        'transaction_no',
        'reference',
        'reference_no',
        'reference_number',
        'loan_number',
        'advice_number',
        'registration_no',
        'vehicle_no',
        'cheque_number',
        'cheque_no',
        'batch_no',
        'batch_number',
        'utr',
        'upi_id',
        'pincode',
        'pin_code',
        'zip',
        'postal_code',
        'phone',
        'mobile',
        'contact_phone',
        'secondary_mobile',
        'fax',
        'alternate_phone',
        'url',
        'website',
        'catalog_link',
        'api_key',
        'secret',
        'token',
        '_token',
        '_method',
        'slug',
        'sections',
        'order',
        'preferences',
        'preference',
        'file',
        'logo_path',
        'path',
        'auth_header',
        'tally_file',
        'receipt_file',
        'logo',
        'logo_emoji',
        'search',
        'q',
        'shortcut-search',
        'sort',
        'filter',
        'action',
        'actions',
        'method',
        'trigger',
        'confirm',
        'confirmation',
        'confirm-user-deletion',
        'remember',
        'tab',
        'rate',
        'amount',
        'price',
        'cost',
        'tax',
        'discount',
        'total',
        'subtotal',
        'qty',
        'quantity',
        'balance',
        'limit',
        'od_limit',
        'interest_rate',
        'cgst',
        'sgst',
        'igst',
        'cess',
        'percentage',
        'percent',
        'margin',
        'mrp',
        'weight',
        'width_cm',
        'width_in',
        'tenure_months',
        'warranty_months',
        'capacity',
        'conversion_factor',
        'annual_turnover',
        'authorized_capital',
        'paid_up_capital',
        'useful_life_years',
        'single_limit',
        'unit_cost',
        'unit_price',
        'selling_price',
        'purchase_price',
        'trade_price',
        'benefit_amount',
        'benefit_percent',
        'min_qty',
        'max_amount',
        'min_amount',
        'min_stock',
        'max_stock',
        'reorder_level',
        'view_name',
        'id',
        // JSON, configuration, metadata and structured dictionary fields
        'additional_details',
        'details',
        'extra_details',
        'meta',
        'metadata',
        'payload',
        'config',
        'configuration',
        'settings',
        'options',
        'params',
        'parameters',
        'attributes',
        'data',
        'properties',
        'context',
        'raw',
        'logs',
        'log',
        'history',
        'audit',
        'bank_accounts',
        'contacts',
        'addresses',
        'items',
        'lines',
        'custom_fields',
        'json_data',
        'raw_data',
        // Enum slugs and technical options
        'type',
        'party_type',
        'party_role',
        'account_type',
        'discount_type',
        'scheme_type',
        'claim_type',
        'operation_type',
        'movement_type',
        'tracking_type',
        'status',
        'stage',
        'gender',
        'msme_category',
        'due_date_basis',
        'billing_mode',
        'opening_balance_type',
        'allocation_basis',
        'allocation_method',
        'role',
        'roles',
        'permission',
        'permissions',
        // Freeform sentences/paragraphs
        'description',
        'notes',
        'remarks',
        'narration',
        'purpose',
        'reason',
        'specification',
        'condition',
        'terms_and_conditions',
        'purchase_terms',
        'sales_terms',
        'selling_terms_and_conditions',
        'purchase_terms_and_conditions',
        'settlement_notes',
    ];

    /**
     * Convert a value to Title Case.
     */
    public static function format(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        return TitleCase::format($trimmed);
    }

    /**
     * Determine if a field name represents a human-readable text field that requires Title Case formatting.
     */
    public static function isApplicableField(string $fieldName): bool
    {
        $field = strtolower(trim($fieldName));

        // Strip bracketed array notation: e.g. 'party[name]' -> 'name', 'items[0][name]' -> 'name'
        if (str_contains($field, '[')) {
            preg_match('/\[([^\]]+)\]$/', $field, $m);
            if (!empty($m[1])) {
                $field = $m[1];
            }
        } elseif (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $field = end($parts);
        }

        // Exclude JSON / structured dictionary field suffixes
        if (str_ends_with($field, '_details') || str_ends_with($field, '_data') || str_ends_with($field, '_meta') || str_ends_with($field, '_config') || str_ends_with($field, '_settings') || str_ends_with($field, '_items') || str_ends_with($field, '_list') || str_ends_with($field, '_json')) {
            return false;
        }

        // 1. Strict Exclusions Check
        if (in_array($field, static::$excludedFields, true)) {
            return false;
        }

        // Exclude foreign keys and ids
        if ($field === 'id' || str_ends_with($field, '_id') || str_ends_with($field, '_ids')) {
            return false;
        }

        // Exclude codes, technical identifiers, phone/pincode
        if (str_ends_with($field, '_code') || str_ends_with($field, '_no') || str_ends_with($field, '_number')) {
            return false;
        }

        // Exclude enum type slugs (e.g. party_type, cheque_type, account_type, discount_type) except descriptive types
        if (!in_array($field, ['company_type', 'product_type', 'expense_type', 'leave_type'], true) && (str_ends_with($field, '_type') || $field === 'type')) {
            return false;
        }

        // Exclude email & password
        if (str_contains($field, 'email') || str_contains($field, 'password')) {
            return false;
        }

        // Exclude gstin, pan, cin, tan, ifsc, sku, hsn
        if (str_contains($field, 'gstin') || str_contains($field, 'ifsc') || in_array($field, ['pan', 'cin', 'tan', 'sku', 'hsn'], true)) {
            return false;
        }

        // Exclude dates and timestamps
        if (str_ends_with($field, '_date') || str_ends_with($field, '_at') || str_ends_with($field, '_on') || str_ends_with($field, '_from') || str_ends_with($field, '_to')) {
            return false;
        }

        // Exclude numeric amounts, rates, quantities, percentages
        if (str_ends_with($field, '_amount') || str_ends_with($field, '_price') || str_ends_with($field, '_rate') || str_ends_with($field, '_cost') || str_ends_with($field, '_qty') || str_ends_with($field, '_percent') || str_ends_with($field, '_percentage') || str_ends_with($field, '_total') || str_ends_with($field, '_value') || str_ends_with($field, '_days')) {
            return false;
        }

        // 2. Explicit Inclusions
        if (in_array($field, static::$explicitFields, true)) {
            return true;
        }

        // 3. Pattern Inclusions
        if (str_ends_with($field, '_name') || str_ends_with($field, '_label') || str_ends_with($field, '_title')) {
            return true;
        }

        // If it reaches here and is not excluded, it is an applicable human-readable text field
        return true;
    }

    /**
     * Backward-compatible alias for isApplicableField.
     */
    public static function isNameField(string $fieldName): bool
    {
        return static::isApplicableField($fieldName);
    }
}
