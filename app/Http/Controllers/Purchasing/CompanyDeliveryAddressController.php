<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\CompanyDeliveryAddress;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Support\NameCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyDeliveryAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        $this->ensureSeeded($company);

        $addresses = CompanyDeliveryAddress::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('address_type')
            ->get()
            ->map(fn (CompanyDeliveryAddress $a) => $a->toApiArray())
            ->values();

        $default = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        return response()->json([
            'success' => true,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'legal_name' => $company->legal_name ?: $company->name,
                'gstin' => $company->gstin,
            ],
            'addresses' => $addresses,
            'default_id' => $default['id'] ?? null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        $validated = $request->validate([
            'address_type' => 'required|string|max:120',
            'mailing_name' => 'nullable|string|max:255',
            'address' => 'required|string|max:2000',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        $addressType = NameCase::format(trim($validated['address_type'])) ?? trim($validated['address_type']);
        $mailingName = isset($validated['mailing_name'])
            ? (NameCase::format(trim((string) $validated['mailing_name'])) ?? trim((string) $validated['mailing_name']))
            : ($company->legal_name ?: $company->name);

        $isDefault = (bool) ($validated['is_default'] ?? false);
        if ($isDefault || ! CompanyDeliveryAddress::where('company_id', $company->id)->where('is_active', true)->exists()) {
            CompanyDeliveryAddress::where('company_id', $company->id)->update(['is_default' => false]);
            $isDefault = true;
        }

        $row = CompanyDeliveryAddress::create([
            'company_id' => $company->id,
            'address_type' => $addressType,
            'mailing_name' => $mailingName !== '' ? $mailingName : ($company->legal_name ?: $company->name),
            'address' => trim($validated['address']),
            'state' => $validated['state'] ?? $company->state,
            'country' => $validated['country'] ?? 'India',
            'pincode' => $validated['pincode'] ?? $company->pincode,
            'gstin' => $validated['gstin'] ?? $company->gstin,
            'is_default' => $isDefault,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'address' => $row->toApiArray(),
        ], 201);
    }

    protected function resolveCompany(Request $request): Company
    {
        $companyId = $request->integer('company_id') ?: null;
        if ($companyId) {
            $company = Company::query()->find($companyId);
            if ($company) {
                return $company;
            }
        }

        $company = Company::resolveCurrent()
            ?? Company::query()->find(auth()->user()?->company_id)
            ?? Company::query()->where('is_default', true)->first()
            ?? Company::query()->first();

        abort_unless($company, 404, 'Company not found.');

        return $company;
    }

    protected function ensureSeeded(Company $company): void
    {
        if (CompanyDeliveryAddress::where('company_id', $company->id)->where('is_active', true)->exists()) {
            return;
        }

        $seeds = [];

        if (trim((string) $company->address) !== '') {
            $seeds[] = [
                'address_type' => 'Primary',
                'mailing_name' => $company->legal_name ?: $company->name,
                'address' => $company->address,
                'state' => $company->state,
                'country' => $company->country ?: 'India',
                'pincode' => $company->pincode,
                'gstin' => $company->gstin,
                'is_default' => true,
            ];
        }

        $warehouses = Warehouse::query()
            ->with('branch')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereNotNull('address')
            ->orderBy('name')
            ->get();

        foreach ($warehouses as $wh) {
            $addr = trim((string) $wh->address);
            if ($addr === '') {
                continue;
            }
            $already = collect($seeds)->contains(fn ($s) => strcasecmp((string) $s['address'], $addr) === 0);
            if ($already) {
                continue;
            }
            $seeds[] = [
                'address_type' => $wh->name ?: 'Warehouse',
                'mailing_name' => $company->legal_name ?: $company->name,
                'address' => $addr,
                'state' => $wh->branch?->state ?: $company->state,
                'country' => 'India',
                'pincode' => $wh->branch?->pincode ?: $company->pincode,
                'gstin' => $wh->branch?->gstin ?: $company->gstin,
                'is_default' => empty($seeds),
            ];
        }

        foreach ($seeds as $seed) {
            CompanyDeliveryAddress::create(array_merge($seed, [
                'company_id' => $company->id,
                'is_active' => true,
            ]));
        }
    }
}
