<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $company = Company::find($user?->company_id) ?? Company::first();

        if (! $company) {
            $company = new Company;
        }

        return view('organization.company-profile', [
            'item' => $company,
            'businessGroups' => BusinessGroup::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $company = Company::find($user?->company_id) ?? Company::first();

        if (! $company) {
            $company = new Company;
        }

        $data = $request->validate([
            'business_group_id' => 'nullable|exists:business_groups,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:companies,code'.($company->exists ? ','.$company->id : ''),
            'legal_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|max:20',
            'pan' => 'nullable|string|max:20',
            'tan' => 'nullable|string|max:20',
            'cin' => 'nullable|string|max:30',
            'udyam_registration_no' => 'nullable|string|max:30',
            'msme_category' => 'nullable|in:none,micro,small,medium',
            'msme_registration_no' => 'nullable|string|max:30',
            'contacts' => 'nullable|array',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.website' => 'nullable|string|max:255',
            'contacts.*.state' => 'nullable|string|max:100',
            'contacts.*.pincode' => 'nullable|string|max:12',
            'contacts.*.address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'address' => 'nullable|string',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'nullable|string|max:255',
            'bank_accounts.*.bank_account_no' => 'nullable|string|max:50',
            'bank_accounts.*.bank_ifsc' => 'nullable|string|max:20',
            'bank_accounts.*.upi_id' => 'nullable|string|max:100',
            'bank_accounts.*.od_limit' => 'nullable|numeric|min:0',
            'bank_accounts.*.interest_rate' => 'nullable|numeric|min:0|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'od_limit' => 'nullable|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'purchase_terms_and_conditions' => 'nullable|string',
            'selling_terms_and_conditions' => 'nullable|string',
            'due_date_basis' => 'nullable|in:invoice_date,inward_date',
            'logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_logo' => 'nullable|boolean',
            'is_active' => 'boolean',
        ], [
            'logo.file' => 'The uploaded logo must be a valid file.',
            'logo.mimes' => 'The company logo must be a file of type: PNG, JPG, JPEG, WEBP, or SVG.',
            'logo.max' => 'The company logo size must not exceed 2 MB.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['due_date_basis'] = $data['due_date_basis'] ?? ($company->due_date_basis ?? 'invoice_date');
        $data['msme_category'] = $data['msme_category'] ?? 'none';

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            Storage::disk('public')->makeDirectory('companies/logos');
            $path = $file->store('companies/logos', 'public');

            if ($path === false) {
                \Illuminate\Support\Facades\Log::error('Failed to store company logo to public disk', [
                    'company_id' => $company->id ?? null,
                    'original_name' => $file->getClientOriginalName(),
                ]);

                return back()->withInput()->withErrors([
                    'logo' => 'Failed to save the uploaded logo to server storage. Please check disk permissions.',
                ]);
            }

            if ($company->logo_path && $company->logo_path !== $path) {
                $oldClean = $company->getCleanLogoPath();
                if ($oldClean && Storage::disk('public')->exists($oldClean)) {
                    Storage::disk('public')->delete($oldClean);
                }
            }
            $data['logo_path'] = $path;
        } elseif ($request->boolean('remove_logo')) {
            if ($company->logo_path) {
                $oldClean = $company->getCleanLogoPath();
                if ($oldClean && Storage::disk('public')->exists($oldClean)) {
                    Storage::disk('public')->delete($oldClean);
                }
            }
            $data['logo_path'] = null;
        }

        if (! $company->exists && ! isset($data['logo_path'])) {
            $data['logo_path'] = null;
        }

        if (blank($data['code'] ?? null)) {
            $data['code'] = $company->code ?: CodeGenerator::forCompany();
        }

        $additionalDetails = $company->additional_details ?? [];

        $contacts = collect($request->input('contacts', []))
            ->filter(fn ($c) => is_array($c) && count(array_filter($c, fn ($val) => ! is_null($val) && $val !== '')) > 0)
            ->values();

        if ($contacts->isNotEmpty()) {
            $primaryContact = $contacts->first();
            $data['phone'] = $primaryContact['phone'] ?? null;
            $data['email'] = $primaryContact['email'] ?? null;
            $data['website'] = $primaryContact['website'] ?? null;
            $data['state'] = $primaryContact['state'] ?? null;
            $data['pincode'] = $primaryContact['pincode'] ?? null;
            $data['address'] = $primaryContact['address'] ?? null;

            $additionalDetails['contacts'] = $contacts->slice(1)->values()->all();
        }

        $bankAccounts = collect($request->input('bank_accounts', []))
            ->filter(fn ($b) => is_array($b) && count(array_filter($b, fn ($val) => ! is_null($val) && $val !== '')) > 0)
            ->values();

        if ($bankAccounts->isNotEmpty()) {
            $primaryBank = $bankAccounts->first();
            $data['bank_name'] = $primaryBank['bank_name'] ?? null;
            $data['bank_account_no'] = $primaryBank['bank_account_no'] ?? null;
            $data['bank_ifsc'] = $primaryBank['bank_ifsc'] ?? null;
            $data['upi_id'] = $primaryBank['upi_id'] ?? null;
            $data['od_limit'] = isset($primaryBank['od_limit']) && is_numeric($primaryBank['od_limit'])
                ? max(0, (float) $primaryBank['od_limit'])
                : (isset($data['od_limit']) ? (float) $data['od_limit'] : 0.0);
            $data['interest_rate'] = isset($primaryBank['interest_rate']) && is_numeric($primaryBank['interest_rate'])
                ? max(0, min(100, (float) $primaryBank['interest_rate']))
                : (isset($data['interest_rate']) ? (float) $data['interest_rate'] : 0.0);

            $additionalDetails['bank_accounts'] = $bankAccounts->slice(1)->values()->all();
        }

        $data['additional_details'] = $additionalDetails;

        unset($data['contacts'], $data['bank_accounts'], $data['logo'], $data['remove_logo']);

        if ($company->exists) {
            $company->update($data);
        } else {
            $company = Company::create($data);
            if ($user) {
                $user->update(['company_id' => $company->id]);
            }
        }

        // Synchronize OD configurations per bank account with OdAccount model
        foreach ($bankAccounts as $bank) {
            $accNo = trim((string) ($bank['bank_account_no'] ?? ''));
            if (empty($accNo)) {
                continue;
            }

            $odLimit = isset($bank['od_limit']) && is_numeric($bank['od_limit'])
                ? max(0, (float) $bank['od_limit'])
                : 0.0;
            $interestRate = isset($bank['interest_rate']) && is_numeric($bank['interest_rate'])
                ? max(0, min(100, (float) $bank['interest_rate']))
                : 0.0;

            $existingOd = \App\Domains\Banking\Models\OdAccount::where('company_id', $company->id)
                ->where('account_number', $accNo)
                ->first();

            if ($existingOd || $odLimit > 0 || $interestRate > 0) {
                \App\Domains\Banking\Models\OdAccount::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'account_number' => $accNo,
                    ],
                    [
                        'bank_name' => $bank['bank_name'] ?? ($existingOd->bank_name ?? 'Bank Account'),
                        'ifsc_code' => $bank['bank_ifsc'] ?? ($existingOd->ifsc_code ?? null),
                        'od_limit' => $odLimit,
                        'interest_rate' => $interestRate,
                        'interest_calculation_method' => $existingOd->interest_calculation_method ?? 'daily_simple',
                        'effective_from' => $existingOd->effective_from ?? now()->startOfMonth()->toDateString(),
                        'status' => 'active',
                        'updated_by' => $user?->id,
                    ]
                );
            }
        }

        return $this->flashSuccess('Company profile updated successfully.', 'organization.company-profile');
    }

    public function logo(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        $company = Company::find($user?->company_id) ?? Company::first();

        $cleanPath = $company?->getCleanLogoPath();

        if (! $company || ! $cleanPath || ! Storage::disk('public')->exists($cleanPath)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
        $headers = [
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
        ];
        if ($extension === 'svg') {
            $headers['Content-Type'] = 'image/svg+xml';
        }

        return Storage::disk('public')->response($cleanPath, null, $headers);
    }
}