<?php

namespace Tally\Http\Requests;

use Tally\Accounting\Money;
use Tally\Http\Requests\Concerns\ResolvesActiveCompany;
use Tally\Inventory\Quantity;
use Tally\Models\Product;
use Tally\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code')));
        $barcode = trim((string) $this->input('barcode'));
        $alternate = $this->filled('alternate_unit_id') ? $this->input('alternate_unit_id') : null;
        $conversion = trim((string) $this->input('conversion_factor'));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $code,
            'barcode' => $barcode === '' ? null : $barcode,
            'hsn_sac_id' => $this->input('hsn_sac_id') ?: null,
            'tax_rate_id' => $this->input('tax_rate_id') ?: null,
            'product_group_id' => $this->input('product_group_id') ?: null,
            'primary_unit_id' => $this->input('primary_unit_id') ?: null,
            'alternate_unit_id' => $alternate,
            'conversion_factor' => $alternate && $conversion !== '' ? $conversion : ($alternate ? $conversion : null),
            'purchase_rate' => $this->blankNumber('purchase_rate'),
            'sales_rate' => $this->blankNumber('sales_rate'),
            'opening_quantity' => $this->blankNumber('opening_quantity', '0'),
            'opening_rate' => $this->blankNumber('opening_rate'),
            'opening_value' => trim((string) $this->input('opening_value')),
            'minimum_stock' => $this->blankNumber('minimum_stock', '0'),
            'reorder_level' => $this->blankNumber('reorder_level', '0'),
            'maximum_stock' => trim((string) $this->input('maximum_stock')) === '' ? null : $this->blankNumber('maximum_stock'),
            'track_batch' => $this->boolean('track_batch'),
            'track_serial' => $this->boolean('track_serial'),
            'is_active' => $this->boolean('is_active'),
        ]);

        if (! $alternate) {
            $this->merge(['conversion_factor' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();
        $product = $this->product();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $product): void {
                    $taken = DB::table('acct_products')
                        ->where('company_id', $company->id)
                        ->when($product, fn ($query) => $query->where('id', '!=', $product->id))
                        ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($taken) {
                        $fail('This product name is already used in the current company.');
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]{0,31}$/',
                Rule::unique('products', 'code')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($product),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $product): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $taken = DB::table('acct_products')
                        ->where('company_id', $company->id)
                        ->when($product, fn ($query) => $query->where('id', '!=', $product->id))
                        ->whereRaw('lower(barcode) = ?', [mb_strtolower((string) $value)])
                        ->exists();
                    $takenExtra = DB::table('acct_product_barcodes')
                        ->where('company_id', $company->id)
                        ->when($product, fn ($query) => $query->where('product_id', '!=', $product->id))
                        ->whereRaw('lower(barcode) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($taken || $takenExtra) {
                        $fail('This barcode is already used in the current company.');
                    }
                },
            ],
            'product_group_id' => [
                'required',
                'integer',
                Rule::exists('product_groups', 'id')->where(function ($query) use ($company, $product) {
                    $query->where('company_id', $company->id)->where(function ($query) use ($product) {
                        $query->where('is_active', true);

                        if ($product) {
                            $query->orWhere('id', $product->product_group_id);
                        }
                    });
                }),
            ],
            'primary_unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(function ($query) use ($company, $product) {
                    $query->where('company_id', $company->id)->where(function ($query) use ($product) {
                        $query->where('is_active', true);

                        if ($product) {
                            $query->orWhere('id', $product->primary_unit_id);
                        }
                    });
                }),
            ],
            'alternate_unit_id' => [
                'nullable',
                'integer',
                'different:primary_unit_id',
                Rule::exists('units', 'id')->where(function ($query) use ($company, $product) {
                    $query->where('company_id', $company->id)->where(function ($query) use ($product) {
                        $query->where('is_active', true);

                        if ($product?->alternate_unit_id) {
                            $query->orWhere('id', $product->alternate_unit_id);
                        }
                    });
                }),
            ],
            'conversion_factor' => [
                Rule::requiredIf(fn () => $this->filled('alternate_unit_id')),
                Rule::prohibitedIf(fn () => ! $this->filled('alternate_unit_id')),
                'nullable',
                'numeric',
                'gt:0',
                'decimal:0,6',
                'max:999999999.999999',
            ],
            'purchase_rate' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'sales_rate' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'opening_quantity' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:99999999999.9999'],
            'opening_rate' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'opening_value' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:99999999999.9999'],
            'reorder_level' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:99999999999.9999'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0', 'decimal:0,4', 'max:99999999999.9999'],
            'track_batch' => ['required', 'boolean'],
            'track_serial' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'hsn_sac_id' => [
                'nullable',
                'integer',
                Rule::exists('hsn_sacs', 'id')->where(function ($query) use ($company, $product) {
                    $query->where('company_id', $company->id)->where(function ($query) use ($product) {
                        $query->where('is_active', true);

                        if ($product?->hsn_sac_id) {
                            $query->orWhere('id', $product->hsn_sac_id);
                        }
                    });
                }),
            ],
            'tax_rate_id' => [
                'nullable',
                'integer',
                Rule::exists('tax_rates', 'id')->where(function ($query) use ($company, $product) {
                    $query->where('company_id', $company->id)->where(function ($query) use ($product) {
                        $query->where('is_active', true);

                        if ($product?->tax_rate_id) {
                            $query->orWhere('id', $product->tax_rate_id);
                        }
                    });
                }),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $company = $this->company();
            $unit = Unit::query()
                ->where('company_id', $company->id)
                ->whereKey($this->input('primary_unit_id'))
                ->first();

            if (! $unit) {
                return;
            }

            foreach (['opening_quantity', 'minimum_stock', 'reorder_level', 'maximum_stock'] as $field) {
                if ($field === 'maximum_stock' && $this->input($field) === null) {
                    continue;
                }
                if (! Quantity::accepts((string) $this->input($field), $unit->decimal_places)) {
                    $validator->errors()->add($field, 'Use at most '.$unit->decimal_places.' decimal places for '.$unit->symbol.'.');
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (Quantity::scale((string) $this->input('reorder_level'), 4) < Quantity::scale((string) $this->input('minimum_stock'), 4)) {
                $validator->errors()->add('reorder_level', 'Reorder level cannot be below the minimum stock.');
            }

            if ($this->input('maximum_stock') !== null && Quantity::scale((string) $this->input('maximum_stock'), 4) < Quantity::scale((string) $this->input('reorder_level'), 4)) {
                $validator->errors()->add('maximum_stock', 'Maximum stock cannot be below the reorder level.');
            }

            if ($this->boolean('track_batch') && $this->boolean('track_serial')) {
                $validator->errors()->add('track_serial', 'Track either batches or serial numbers on one product.');
            }

            try {
                $expected = Product::openingValue((string) $this->input('opening_quantity'), (string) $this->input('opening_rate'));
            } catch (\InvalidArgumentException $exception) {
                $validator->errors()->add('opening_value', $exception->getMessage());

                return;
            }

            $given = trim((string) $this->input('opening_value'));

            if ($given !== '' && Money::cents($given) !== Money::cents($expected)) {
                $validator->errors()->add('opening_value', 'Opening value must equal opening quantity × opening rate ('.$expected.').');
            }
        });
    }

    /**
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        if (trim((string) ($data['opening_value'] ?? '')) === '') {
            $data['opening_value'] = Product::openingValue(
                (string) $data['opening_quantity'],
                (string) $data['opening_rate'],
            );
        }

        if (($data['conversion_factor'] ?? null) === '') {
            $data['conversion_factor'] = null;
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'This SKU is already used in the current company.',
            'product_group_id.exists' => 'Select an active product group from the current company.',
            'primary_unit_id.exists' => 'Select an active unit from the current company.',
            'alternate_unit_id.exists' => 'Select an active alternate unit from the current company.',
            'alternate_unit_id.different' => 'The alternate unit must be different from the primary unit.',
            'conversion_factor.required' => 'Enter how many primary units make one alternate unit.',
            'conversion_factor.prohibited' => 'Remove the conversion factor when there is no alternate unit.',
        ];
    }

    public function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }

    private function blankNumber(string $field, string $fallback = '0'): string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? $fallback : $value;
    }
}
