<?php

namespace App\Domains\Master\Imports;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Master\Models\Route;
use App\Domains\Organization\Models\Company;
use App\Support\CodeGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import Parties (Customers / Suppliers).
 *
 * Template columns (case-insensitive):
 *   name, code, party_type, classification, trade_name, gstin, phone, email,
 *   opened_on, reference_by, udyam_number, website, location_link, is_active,
 *   area, route,
 *   address_line_1, address_line_2, city, state, pincode,
 *   credit_limit, credit_days, credit_status, credit_period_basis, interest_rate,
 *   contact_name, contact_role, contact_phone, contact_email,
 *   bank_account_holder, bank_name, bank_branch, bank_account_number, bank_account_type, bank_ifsc
 *
 * Legacy aliases: customer_type → classification; address → address_line_1.
 * Match on code. Classification / area / route auto-created by name.
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
        $types = CustomerType::query()->get()->keyBy(fn ($t) => strtolower((string) $t->name));
        $areas = Area::query()->get()->keyBy(fn ($a) => strtolower((string) $a->name));
        $routes = Route::query()->get()->keyBy(fn ($r) => strtolower((string) $r->name));
        $companyId = $this->companyId ?? Company::query()->value('id');
        $branchId = auth()->user()?->branch_id;

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = collect($row)->mapWithKeys(fn ($v, $k) => [strtolower((string) $k) => $v])->toArray();

            $name = \App\Support\NameCase::format((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'name', 'message' => 'Name is required'];
                continue;
            }

            $typeName = \App\Support\NameCase::format((string) ($data['classification'] ?? $data['customer_type'] ?? 'RETAIL'));
            $type = $types[strtolower($typeName)] ?? null;
            if (! $type) {
                $type = CustomerType::firstOrCreate(
                    ['name' => $typeName],
                    [
                        'code' => strtoupper(Str::slug($typeName, '_')),
                        'is_active' => true,
                    ]
                );
                $types->put(strtolower($type->name), $type);
            }

            $code = trim((string) ($data['code'] ?? '')) ?: CodeGenerator::forCustomer($companyId);
            $existing = Customer::query()->whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

            $area = null;
            if (filled($data['area'] ?? null)) {
                $areaName = trim((string) $data['area']);
                $area = $areas[strtolower($areaName)] ?? Area::firstOrCreate(
                    ['name' => $areaName],
                    [
                        'code' => strtoupper(Str::slug($areaName, '_')),
                        'is_active' => true,
                    ]
                );
                $areas->put(strtolower($area->name), $area);
            }

            $route = null;
            if (filled($data['route'] ?? null)) {
                $routeName = trim((string) $data['route']);
                $route = $routes[strtolower($routeName)] ?? Route::firstOrCreate(
                    ['name' => $routeName],
                    [
                        'code' => strtoupper(Str::slug($routeName, '_')),
                        'area_id' => $area?->id,
                        'is_active' => true,
                    ]
                );
                $routes->put(strtolower($route->name), $route);
            }

            $addressLine1 = trim((string) ($data['address_line_1'] ?? $data['address'] ?? '')) ?: null;
            $addressLine2 = trim((string) ($data['address_line_2'] ?? '')) ?: null;
            $city = trim((string) ($data['city'] ?? '')) ?: null;
            $state = trim((string) ($data['state'] ?? '')) ?: null;
            $pincode = trim((string) ($data['pincode'] ?? '')) ?: null;

            $flatAddress = $addressLine1;
            if ($addressLine2) {
                $flatAddress = trim(($flatAddress ? $flatAddress.', ' : '').$addressLine2);
            }

            $creditStatus = strtolower(trim((string) ($data['credit_status'] ?? 'open')));
            if (! in_array($creditStatus, ['open', 'hold', 'blocked', 'restricted', 'frozen', 'active'], true)) {
                $creditStatus = 'open';
            }

            $creditBasis = strtolower(trim((string) ($data['credit_period_basis'] ?? 'cumulative')));
            if (! in_array($creditBasis, ['cumulative', 'invoice_date', 'inward_date', 'receipt_date', 'monthly', 'quarterly', 'yearly'], true)) {
                $creditBasis = 'cumulative';
            }

            $isActive = array_key_exists('is_active', $data) && $data['is_active'] !== null && $data['is_active'] !== ''
                ? $this->isTruthy($data['is_active'])
                : true;

            $openedOn = trim((string) ($data['opened_on'] ?? '')) ?: null;
            if ($openedOn !== null) {
                try {
                    $openedOn = \Carbon\Carbon::parse($openedOn)->toDateString();
                } catch (\Throwable) {
                    $this->skipped++;
                    $this->errors[] = ['row' => $rowNo, 'field' => 'opened_on', 'message' => "Invalid date '{$data['opened_on']}'"];
                    continue;
                }
            }

            $payload = [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'name' => $name,
                'trade_name' => trim((string) ($data['trade_name'] ?? '')) ?: null,
                'code' => $code,
                'party_type' => match (strtolower(trim(str_replace([' ', '-'], '_', (string) ($data['party_type'] ?? ''))))) {
                    'sundry_creditors', 'creditors', 'creditor', 'supplier' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
                    'both' => Customer::PARTY_TYPE_BOTH,
                    default => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
                },
                'customer_type_id' => $type->id,
                'area_id' => $area?->id,
                'route_id' => $route?->id,
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'email' => trim((string) ($data['email'] ?? '')) ?: null,
                'opened_on' => $openedOn,
                'reference_by' => trim((string) ($data['reference_by'] ?? '')) ?: null,
                'udyam_number' => trim((string) ($data['udyam_number'] ?? '')) ?: null,
                'website' => trim((string) ($data['website'] ?? '')) ?: null,
                'location_link' => trim((string) ($data['location_link'] ?? '')) ?: null,
                'address' => $flatAddress,
                'state' => $state,
                'pincode' => $pincode,
                'gstin' => trim((string) ($data['gstin'] ?? '')) ?: null,
                'credit_limit' => (float) ($data['credit_limit'] ?? 0),
                'credit_days' => (int) ($data['credit_days'] ?? 0),
                'interest_rate' => (float) ($data['interest_rate'] ?? 18),
                'credit_period_basis' => $creditBasis,
                'credit_status' => $creditStatus,
                'is_active' => $isActive,
            ];

            if ($existing) {
                $existing->update($payload);
                $customer = $existing;
                $this->updated++;
            } else {
                $customer = Customer::create($payload);
                $this->created++;
            }

            if (filled($data['contact_name'] ?? null) || filled($data['contact_phone'] ?? null)) {
                $contactName = \App\Support\NameCase::format((string) ($data['contact_name'] ?? ''));
                PartyContact::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'phone' => (string) ($data['contact_phone'] ?? ''),
                        'name' => $contactName,
                    ],
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

            if ($addressLine1 || $city || $state || $pincode) {
                PartyAddress::updateOrCreate(
                    ['customer_id' => $customer->id, 'type' => 'billing', 'label' => 'Head Office'],
                    [
                        'name' => $name,
                        'address_line_1' => $addressLine1,
                        'address_line_2' => $addressLine2,
                        'city' => $city,
                        'address' => $flatAddress,
                        'state' => $state,
                        'pincode' => $pincode,
                        'gstin' => trim((string) ($data['gstin'] ?? '')) ?: null,
                        'is_default' => true,
                        'is_default_billing' => true,
                        'is_active' => true,
                    ]
                );
            }

            $hasBank = filled($data['bank_account_number'] ?? null)
                || filled($data['bank_ifsc'] ?? null)
                || filled($data['bank_name'] ?? null);
            if ($hasBank) {
                $accountNumber = trim((string) ($data['bank_account_number'] ?? ''));
                PartyBankAccount::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'account_number' => $accountNumber !== '' ? $accountNumber : 'PRIMARY',
                    ],
                    [
                        'account_holder_name' => trim((string) ($data['bank_account_holder'] ?? $name)) ?: $name,
                        'bank_name' => trim((string) ($data['bank_name'] ?? '')) ?: null,
                        'branch_name' => trim((string) ($data['bank_branch'] ?? '')) ?: null,
                        'account_type' => trim((string) ($data['bank_account_type'] ?? 'Current')) ?: 'Current',
                        'ifsc_code' => strtoupper(trim((string) ($data['bank_ifsc'] ?? ''))) ?: null,
                        'is_primary' => true,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    protected function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
