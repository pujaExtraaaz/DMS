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
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'purchase_terms_and_conditions' => 'nullable|string',
            'selling_terms_and_conditions' => 'nullable|string',
            'due_date_basis' => 'nullable|in:invoice_date,inward_date',
            'logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_logo' => 'nullable|boolean',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['due_date_basis'] = $data['due_date_basis'] ?? ($company->due_date_basis ?? 'invoice_date');
        $data['msme_category'] = $data['msme_category'] ?? 'none';

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('companies/logos', 'public');
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $path;
        } elseif ($request->boolean('remove_logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
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

        return $this->flashSuccess('Company profile updated successfully.', 'organization.company-profile');
    }

    public function logo(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        $company = Company::find($user?->company_id) ?? Company::first();

        if (! $company || ! $company->logo_path || ! Storage::disk('public')->exists($company->logo_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($company->logo_path);
    }
}