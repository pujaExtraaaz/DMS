<?php

namespace App\Domains\Master\Imports;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use Illuminate\Support\Str;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Master\Models\Route;
use App\Domains\Organization\Models\Company;
use App\Support\CodeGenerator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import Parties (Customers/Suppliers/Dealers).
 * Accepts columns: name, code, party_type, classification (customer type name),
 *   area, route, phone, email, gstin, address, state, pincode,
 *   credit_limit, credit_days, interest_rate,
 *   contact_name, contact_phone, contact_email, contact_role.
 *
 * Match on `code` (case-insensitive). Duplicate rows update. Missing code auto-generated.
 */
class PartiesImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public array $errors = [];
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        $types = CustomerType::query()->get()->keyBy(fn($t) => strtolower($t->name));
        $areas = Area::query()->get()->keyBy(fn($a) => strtolower($a->name));
        $routes = Route::query()->get()->keyBy(fn($r) => strtolower($r->name));
        $companyId = $this->companyId ?? Company::query()->value('id');
        $branchId = auth()->user()?->branch_id;

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = collect($row)->mapWithKeys(fn($v, $k) => [strtolower((string) $k) => $v])->toArray();

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'name', 'message' => 'Name is required'];
                continue;
            }

            $typeName = trim((string) ($data['classification'] ?? $data['customer_type'] ?? 'Retail'));

            $type = $types[strtolower($typeName)] ?? null;

            if (! $type) {
                $type = CustomerType::firstOrCreate(
                    ['name' => $typeName],
                    [
                        'code' => strtoupper(Str::slug($typeName, '_')),
                        'is_active' => true,
                    ]
                );

                $types->put(strtolower($typeName), $type);
            }

            $customerType = $type;

            if (! $types->has(strtolower($type->name))) $types->put(strtolower($type->name), $type);

            $code = trim((string) ($data['code'] ?? '')) ?: CodeGenerator::forCustomer($companyId);
            $existing = Customer::query()->whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

            $area = filled($data['area'] ?? null)
            ? ($areas[strtolower((string) $data['area'])] ?? Area::firstOrCreate(
                ['name' => trim((string) $data['area'])],
                [
                    'code' => strtoupper(Str::slug(trim((string) $data['area']), '_')),
                    'is_active' => true,
                ]
            ))
            : null;
            if ($area && ! $areas->has(strtolower($area->name))) $areas->put(strtolower($area->name), $area);

            $route = filled($data['route'] ?? null)
                ? ($routes[strtolower((string) $data['route'])] ?? Route::firstOrCreate(
                    ['name' => trim((string) $data['route'])],
                    [
                        'code' => strtoupper(Str::slug(trim((string) $data['route']), '_')),
                        'is_active' => true,
                    ]
                ))
                : null;

            if ($route && ! $routes->has(strtolower($route->name))) {
                $routes->put(strtolower($route->name), $route);
            }
            if ($route && ! $routes->has(strtolower($route->name))) $routes->put(strtolower($route->name), $route);

            $payload = [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'name' => $name,
                'code' => $code,
                'party_type' => in_array(strtolower((string) ($data['party_type'] ?? 'customer')), ['dealer', 'customer', 'supplier', 'both'], true)
                    ? strtolower((string) $data['party_type'])
                    : 'customer',
                'customer_type_id' => $type->id,
                'area_id' => $area?->id,
                'route_id' => $route?->id,
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'email' => trim((string) ($data['email'] ?? '')) ?: null,
                'address' => trim((string) ($data['address'] ?? '')) ?: null,
                'state' => trim((string) ($data['state'] ?? '')) ?: null,
                'pincode' => trim((string) ($data['pincode'] ?? '')) ?: null,
                'gstin' => trim((string) ($data['gstin'] ?? '')) ?: null,
                'credit_limit' => (float) ($data['credit_limit'] ?? 0),
                'credit_days' => (int) ($data['credit_days'] ?? 0),
                'interest_rate' => (float) ($data['interest_rate'] ?? 18),
                'credit_period_basis' => 'cumulative',
                'credit_status' => 'open',
                'is_active' => true,
            ];

            if ($existing) {
                $existing->update($payload);
                $customer = $existing;
                $this->updated++;
            } else {
                $customer = Customer::create($payload);
                $this->created++;
            }

            // Optional child contact/address rows in the same sheet:
            if (filled($data['contact_name'] ?? null) || filled($data['contact_phone'] ?? null)) {
                PartyContact::updateOrCreate(
                    ['customer_id' => $customer->id, 'phone' => (string) ($data['contact_phone'] ?? ''), 'name' => (string) ($data['contact_name'] ?? '')],
                    [
                       'level' => match (strtolower(trim((string) ($data['level'] ?? 'party')))) {
                            'primary', 'party' => 'party',
                            'branch' => 'branch',
                            'site' => 'site',
                            'warehouse' => 'warehouse',
                            'transporter' => 'transporter',
                            'driver' => 'driver',
                            'site_incharge', 'site incharge' => 'site_incharge',
                            default => 'party',
                        },
                        'role' => trim((string) ($data['contact_role'] ?? '')) ?: null,
                        'email' => trim((string) ($data['contact_email'] ?? '')) ?: null,
                        'is_primary' => true,
                        'is_active' => true,
                    ]
                );
            }

            if (filled($data['address'] ?? null)) {
                PartyAddress::updateOrCreate(
                    ['customer_id' => $customer->id, 'type' => 'billing', 'label' => 'Head Office'],
                    [
                        'name' => $name,
                        'address' => trim((string) $data['address']),
                        'state' => trim((string) ($data['state'] ?? '')) ?: null,
                        'pincode' => trim((string) ($data['pincode'] ?? '')) ?: null,
                        'gstin' => trim((string) ($data['gstin'] ?? '')) ?: null,
                        'is_default' => true,
                    ]
                );
            }
        }
    }
}
