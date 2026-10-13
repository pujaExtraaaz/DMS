<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class CompanyController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Company::query()
            ->with('businessGroup')
            ->when($request->filled('business_group_id'), fn ($q) => $q->where('business_group_id', $request->business_group_id))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'gstin', 'email']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'is_active', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('organization.companies.index', [
            'items' => $items,
            'businessGroups' => BusinessGroup::where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'businessGroupId' => $request->input('business_group_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('organization.companies.form', [
            'item' => new Company,
            'businessGroups' => BusinessGroup::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Company::create($this->validated($request));

        return $this->flashSuccess('Company created successfully.', 'organization.companies.index');
    }

    public function show(Company $company): View
    {
        $company->load(['businessGroup', 'branches', 'warehouses', 'financialYears']);

        return view('organization.companies.show', [
            'company' => $company,
        ]);
    }

    public function edit(Company $company): View
    {
        return view('organization.companies.form', [
            'item' => $company,
            'businessGroups' => BusinessGroup::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validated($request, $company);

        if ($company->is_default && empty($data['is_active'])) {
            return back()->withInput()->with('error', 'Cannot deactivate the default company. Please set another active company as default before deactivating this one.');
        }

        $company->update($data);

        return $this->flashSuccess('Company updated successfully.', 'organization.companies.index');
    }

    public function setDefault(Request $request, Company $company): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasRole('super-admin') && ! $user->can('organization.manage')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to change the default company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to change the default company.');
        }

        if (! $company->is_active) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot set an inactive company as the default company.',
                ], 422);
            }

            return back()->with('error', 'Cannot set an inactive company as the default company.');
        }

        $company->setAsDefault();

        \Illuminate\Support\Facades\Log::info("Default company changed to ID {$company->id} ({$company->name}) by user ID " . ($user?->id ?? 'system'));

        if (class_exists(\Tally\Audit\AuditLogger::class)) {
            try {
                app(\Tally\Audit\AuditLogger::class)->record('default_company_changed', 'organization', $company, "Default company changed to {$company->name}.");
            } catch (\Throwable) {}
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Company '{$company->name}' is now set as the default company.",
            ]);
        }

        return $this->flashSuccess("Company '{$company->name}' is now set as the default company.", 'organization.companies.index');
    }

    public function destroy(Request $request, Company $company): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasRole('super-admin') && ! $user->can('organization.manage')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete company.');
        }

        $blockReason = $company->getDeleteBlockReason();
        if ($blockReason) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $blockReason,
                ], 422);
            }

            return back()->with('error', $blockReason);
        }

        if ($company->logo_path) {
            $cleanPath = $company->getCleanLogoPath();
            if ($cleanPath && Storage::disk('public')->exists($cleanPath)) {
                Storage::disk('public')->delete($cleanPath);
            }
        }

        $companyName = $company->name;
        $company->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Company '{$companyName}' deleted successfully.",
            ]);
        }

        return $this->flashSuccess("Company '{$companyName}' deleted successfully.", 'organization.companies.index');
    }

    protected function validated(Request $request, ?Company $company = null): array
    {
        $data = $request->validate([
            'business_group_id' => 'nullable|exists:business_groups,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:companies,code'.($company ? ','.$company->id : ''),
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
        ], [
            'logo.file' => 'The uploaded logo must be a valid file.',
            'logo.mimes' => 'The company logo must be a file of type: PNG, JPG, JPEG, WEBP, or SVG.',
            'logo.max' => 'The company logo size must not exceed 2 MB.',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['due_date_basis'] = $data['due_date_basis'] ?? ($company?->due_date_basis ?? 'invoice_date');
        $data['msme_category'] = $data['msme_category'] ?? 'none';

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            Storage::disk('public')->makeDirectory('companies/logos');
            $path = $file->store('companies/logos', 'public');

            if ($path === false) {
                \Illuminate\Support\Facades\Log::error('Failed to store company logo to public disk', [
                    'company_id' => $company?->id ?? null,
                    'original_name' => $file->getClientOriginalName(),
                ]);

                return back()->withInput()->withErrors([
                    'logo' => 'Failed to save the uploaded logo to server storage. Please check disk permissions.',
                ]);
            }

            if ($company?->logo_path && $company->logo_path !== $path) {
                $oldClean = $company->getCleanLogoPath();
                if ($oldClean && Storage::disk('public')->exists($oldClean)) {
                    Storage::disk('public')->delete($oldClean);
                }
            }
            $data['logo_path'] = $path;
        } elseif ($request->boolean('remove_logo')) {
            if ($company?->logo_path) {
                $oldClean = $company->getCleanLogoPath();
                if ($oldClean && Storage::disk('public')->exists($oldClean)) {
                    Storage::disk('public')->delete($oldClean);
                }
            }
            $data['logo_path'] = null;
        }

        if (blank($data['code'] ?? null)) {
            $data['code'] = $company?->code ?: CodeGenerator::forCompany();
        }

        $additionalDetails = $company?->additional_details ?? [];

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

        return $data;
    }
}