<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Master\Services\GstLookupService;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CompanyProfileController extends Controller
{
    /**
     * Show the Company Profile page for the current authenticated company.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        // Resolve company with strict multi-company data isolation
        if ($isSuperAdmin && $request->filled('company_id')) {
            $company = Company::findOrFail($request->input('company_id'));
        } else {
            $company = $user->company ?? Company::query()->firstOrFail();
        }

        $allCompanies = $isSuperAdmin ? Company::query()->orderBy('name')->get() : collect([$company]);

        return view('organization.companies.profile', [
            'company' => $company,
            'allCompanies' => $allCompanies,
            'isSuperAdmin' => $isSuperAdmin,
            'businessGroups' => BusinessGroup::where('is_active', true)->orderBy('name')->get(),
            'states' => GstLookupService::states(),
        ]);
    }

    /**
     * Update the Company Profile details and logo.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        // Prevent IDOR: standard users can only ever update their own assigned company
        if ($isSuperAdmin && $request->filled('company_id')) {
            $company = Company::findOrFail($request->input('company_id'));
        } else {
            $company = $user->company ?? Company::query()->firstOrFail();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'business_group_id' => 'nullable|exists:business_groups,id',
            'gstin' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            ],
            'pan' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i',
            ],
            'cin' => 'nullable|string|max:30',
            'tan' => 'nullable|string|max:20',
            'udyam_registration_no' => 'nullable|string|max:30',
            'msme_category' => 'nullable|in:none,micro,small,medium',
            'msme_registration_no' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'country' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'purchase_terms_and_conditions' => 'nullable|string',
            'selling_terms_and_conditions' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ], [
            'gstin.regex' => 'The GSTIN must be a valid 15-character Indian GST identification number (e.g. 27AAPFU0939F1ZV).',
            'pan.regex' => 'The PAN must be a valid 10-character Indian Permanent Account Number (e.g. AAPFU0939F).',
            'logo.max' => 'The company logo must not be larger than 2MB.',
            'logo.mimes' => 'The company logo must be a file of type: JPG, JPEG, PNG, or WEBP.',
        ]);

        // 1. Handle Logo Removal
        if ($request->boolean('remove_logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $validated['logo_path'] = null;
        }

        // 2. Handle Logo Upload & Replacement
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $oldPath = $company->logo_path;

            $filename = 'logo_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $newPath = $file->storeAs("companies/{$company->id}/branding", $filename, 'public');

            $validated['logo_path'] = $newPath;

            // Delete previous logo after successful upload
            if ($oldPath && Storage::disk('public')->exists($oldPath) && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // 3. Normalize Formats
        if (! empty($validated['gstin'])) {
            $validated['gstin'] = strtoupper(trim($validated['gstin']));
        }
        if (! empty($validated['pan'])) {
            $validated['pan'] = strtoupper(trim($validated['pan']));
        }
        if (! empty($validated['tan'])) {
            $validated['tan'] = strtoupper(trim($validated['tan']));
        }
        if (! empty($validated['bank_ifsc'])) {
            $validated['bank_ifsc'] = strtoupper(trim($validated['bank_ifsc']));
        }

        unset($validated['logo'], $validated['remove_logo']);

        $company->update($validated);

        $redirectParams = $isSuperAdmin && $request->filled('company_id')
            ? ['company_id' => $company->id]
            : [];

        return $this->flashSuccess('Company Profile updated successfully.', 'organization.company-profile.edit', $redirectParams);
    }

    /**
     * Stream the company logo directly.
     */
    public function logo(Company $company): Response
    {
        if (! $company->logo_path) {
            abort(404);
        }

        $cleanPath = ltrim($company->logo_path, '/');
        if (str_starts_with($cleanPath, 'public/')) {
            $cleanPath = substr($cleanPath, 7);
        }
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        if (Storage::disk('public')->exists($cleanPath)) {
            return Storage::disk('public')->response($cleanPath);
        }

        if (Storage::disk('local')->exists($cleanPath)) {
            return Storage::disk('local')->response($cleanPath);
        }

        $filePath = storage_path('app/public/'.$cleanPath);
        if (file_exists($filePath)) {
            return response()->file($filePath);
        }

        abort(404);
    }
}