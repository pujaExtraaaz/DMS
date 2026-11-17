<?php

namespace App\Domains\Master\Imports;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Organization\Models\WarehouseType;
use App\Support\CodeGenerator;
use App\Support\IndianCities;
use App\Support\IndianStates;
use App\Support\NameCase;
use App\Support\WorldCountries;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehousesImport implements ToCollection, WithHeadingRow
{
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $companies = Company::query()->get()->keyBy(fn ($c) => strtolower((string) $c->code));
        $branches = Branch::query()->get()->keyBy(
            fn ($b) => strtolower($b->company_id.'|'.(string) $b->code)
        );
        $stateNames = IndianStates::namesAlphabetical();
        $countryNames = WorldCountries::all();

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            $name = NameCase::format($this->str($data, 'name')) ?? '';
            if ($name === '') {
                $this->skip($rowNo, 'name', 'Warehouse name is required');
                continue;
            }

            $companyCode = $this->str($data, 'company_code');
            $company = $companies[strtolower($companyCode)] ?? null;
            if (! $company) {
                $this->skip($rowNo, 'company_code', "Company '{$companyCode}' not found");
                continue;
            }

            $branchCode = $this->str($data, 'branch_code');
            $branch = $branches[strtolower($company->id.'|'.$branchCode)] ?? null;
            if (! $branch) {
                $this->skip($rowNo, 'branch_code', "Branch '{$branchCode}' not found for company '{$companyCode}'");
                continue;
            }

            $address = NameCase::format($this->str($data, 'address')) ?? '';
            if ($address === '') {
                $this->skip($rowNo, 'address', 'Address Line 1 is required');
                continue;
            }

            $state = IndianStates::normalizeName($this->str($data, 'state'));
            if ($state === '' || ! in_array($state, $stateNames, true)) {
                $this->skip($rowNo, 'state', 'Valid Indian state is required');
                continue;
            }

            $city = NameCase::format($this->str($data, 'city')) ?? '';
            if ($city === '') {
                $this->skip($rowNo, 'city', 'City is required');
                continue;
            }
            $cities = IndianCities::getCitiesForState($state);
            $cityOk = collect($cities)->contains(fn ($c) => strcasecmp((string) $c, $city) === 0);
            if (! $cityOk) {
                $this->skip($rowNo, 'city', "City '{$city}' does not belong to state '{$state}'");
                continue;
            }

            $country = $this->str($data, 'country') ?: 'India';
            if (! in_array($country, $countryNames, true)) {
                $this->skip($rowNo, 'country', "Country '{$country}' is not supported");
                continue;
            }

            $warehouseType = NameCase::format($this->str($data, 'warehouse_type')) ?: null;
            if ($warehouseType && Schema::hasTable('warehouse_types')) {
                WarehouseType::query()->firstOrCreate(
                    ['name' => $warehouseType],
                    ['is_active' => true]
                );
            }

            $code = $this->str($data, 'code');
            if ($code === '') {
                $code = CodeGenerator::forWarehouse($company->id);
            }

            $isDefault = $this->boolOrDefault($data, 'is_default', false);
            if ($isDefault) {
                Warehouse::query()
                    ->where('company_id', $company->id)
                    ->where('branch_id', $branch->id)
                    ->update(['is_default' => false]);
            }

            $payload = [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $name,
                'code' => $code,
                'warehouse_type' => $warehouseType,
                'contact_person' => NameCase::format($this->str($data, 'contact_person')) ?: null,
                'contact_phone' => $this->nullableStr($data, 'contact_phone'),
                'address' => $address,
                'address_line_2' => NameCase::format($this->str($data, 'address_line_2')) ?: null,
                'state' => $state,
                'city' => $city,
                'country' => $country,
                'pincode' => $this->nullableStr($data, 'pincode'),
                'notes' => $this->nullableStr($data, 'notes'),
                'is_default' => $isDefault,
                'is_active' => $this->boolOrDefault($data, 'is_active', true),
            ];

            $existing = Warehouse::query()
                ->where('company_id', $company->id)
                ->whereRaw('LOWER(code) = ?', [strtolower($code)])
                ->first();

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                Warehouse::create($payload);
                $this->created++;
            }
        }
    }
}
