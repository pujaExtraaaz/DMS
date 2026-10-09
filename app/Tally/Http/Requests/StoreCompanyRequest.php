<?php

namespace Tally\Http\Requests;

use Tally\Models\Company;
use Tally\Tax\GstRegistrationType;
use Tally\Tax\TaxPricing;
use Tally\Tax\TaxRounding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $start = $this->normalizeDate($this->input('financial_year_start'));
        $end = $this->normalizeDate($this->input('financial_year_end'));
        $books = $this->normalizeDate($this->input('books_beginning_from'));

        if ($start && $end === null) {
            $end = Carbon::parse($start)->addYear()->subDay()->toDateString();
        }

        if ($start && $books === null) {
            $books = $start;
        }

        $name = trim((string) $this->input('name'));
        $mailing = trim((string) $this->input('mailing_name'));
        $symbol = trim((string) $this->input('currency_symbol'));
        $formal = trim((string) $this->input('currency_formal_name'));

        $cleaned = [
            'name' => $name,
            'country' => trim((string) $this->input('country')) ?: 'India',
            'mailing_name' => $mailing !== '' ? $mailing : ($name !== '' ? $name : null),
            'currency_symbol' => $symbol !== '' ? $symbol : '₹',
            'currency_formal_name' => $formal !== '' ? strtoupper($formal) : 'INR',
            'financial_year_start' => $start,
            'financial_year_end' => $end,
            'books_beginning_from' => $books,
            'is_active' => $this->boolean('is_active'),
            'allow_negative_stock' => $this->boolean('allow_negative_stock'),
        ];

        foreach (['data_path', 'legal_name', 'address', 'city', 'state', 'pincode', 'phone', 'mobile', 'fax', 'email', 'website', 'gstin', 'gst_registration_type', 'pan', 'tax_pricing', 'tax_rounding', 'sales_terms', 'purchase_terms'] as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                $cleaned[$field] = null;

                continue;
            }

            $value = trim($value);

            if (in_array($field, ['gstin', 'pan'], true)) {
                $value = strtoupper((string) preg_replace('/\s+/', '', $value));
            }

            $cleaned[$field] = $value === '' ? null : $value;
        }

        $cleaned['tax_pricing'] ??= TaxPricing::Exclusive->value;
        $cleaned['tax_rounding'] ??= TaxRounding::Paisa->value;

        $this->merge($cleaned);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'name' => ['required', 'string', 'max:255'],
            'data_path' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'mailing_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'fax' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'gst_registration_type' => ['nullable', Rule::enum(GstRegistrationType::class)],
            'tax_pricing' => ['required', Rule::enum(TaxPricing::class)],
            'tax_rounding' => ['required', Rule::enum(TaxRounding::class)],
            'allow_negative_stock' => ['required', 'boolean'],
            'gstin' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
                Rule::unique('companies', 'gstin')->ignore($company instanceof Company ? $company : null),
            ],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'sales_terms' => ['nullable', 'string', 'max:2000'],
            'purchase_terms' => ['nullable', 'string', 'max:2000'],
            'financial_year_start' => ['required', 'date'],
            'financial_year_end' => ['required', 'date', 'after:financial_year_start'],
            'books_beginning_from' => ['required', 'date', 'after_or_equal:financial_year_start', 'before_or_equal:financial_year_end'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'currency_formal_name' => ['required', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gstin.regex' => 'Enter a valid 15-character GSTIN.',
            'gstin.size' => 'Enter a valid 15-character GSTIN.',
            'pan.regex' => 'Enter a valid 10-character PAN.',
            'pan.size' => 'Enter a valid 10-character PAN.',
            'financial_year_end.after' => 'The financial year end must be after the start date.',
            'books_beginning_from.after_or_equal' => 'Books beginning from must be on or after the financial year beginning.',
            'books_beginning_from.before_or_equal' => 'Books beginning from must fall inside the financial year.',
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'j-M-y', 'j-M-Y', 'd-M-y', 'd-M-Y', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat('!'.$format, $value);
                $errors = Carbon::getLastErrors();

                if ($parsed instanceof Carbon && ($errors === false || (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0))) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $value;
    }
}
