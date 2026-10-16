<?php

namespace App\Support;

class NameCase
{
    /**
     * Fields that explicitly represent names and must always be formatted in uppercase.
     */
    protected static array $explicitNameFields = [
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
        'account_holder_name',
        'shipping_name',
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
    ];

    /**
     * Technical and non-name fields that must NOT be converted to uppercase.
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
        'email',
        'secondary_email',
        'contact_email',
        'password',
        'password_confirmation',
        'gstin',
        'pan',
        'sku',
        'hsn_code',
        'code',
        'serial_no',
        'token',
        'url',
        'slug',
        'search',
        'q',
        '_token',
        '_method',
    ];

    /**
     * Convert a name value to FULL UPPERCASE LETTERS.
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

        return mb_strtoupper($trimmed, 'UTF-8');
    }

    /**
     * Determine if a field name represents a name field that requires uppercase formatting.
     */
    public static function isNameField(string $fieldName): bool
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

        if (in_array($field, static::$excludedFields, true)) {
            return false;
        }

        if (in_array($field, static::$explicitNameFields, true)) {
            return true;
        }

        if (str_ends_with($field, '_name')) {
            return true;
        }

        return false;
    }
}

