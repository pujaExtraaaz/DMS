<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use App\Support\IndianStates;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View|JsonResponse
    {
        $query = Company::query()
            ->with(['businessGroup', 'branches', 'warehouses'])
            ->when($request->filled('business_group_id'), fn ($q) => $q->where('business_group_id', $request->business_group_id))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'gstin', 'pan', 'city', 'state', 'email']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'is_active', 'created_at', 'company_type', 'city', 'state'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        if ($request->wantsJson() && ! $request->hasHeader('X-DMS-Dynamic')) {
            return response()->json([
                'success' => true,
                'items' => $items,
            ]);
        }

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
            'item' => new Company([
                'country' => 'India',
                'company_type' => 'Private Limited Company',
                'relationship_type' => 'Sister Concern',
                'compliance_status' => 'Compliant',
                'logo_emoji' => '🏢',
                'is_active' => true,
            ]),
            'businessGroups' => BusinessGroup::where('is_active', true)->orderBy('name')->get(),
            'states' => IndianStates::names(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request);

        $company = Company::create($data);

        if ($company->business_group_id) {
            $company->businessGroup?->companies()->syncWithoutDetaching([$company->id]);
        }

        if ($request->wantsJson()) {
            $company->load(['businessGroup', 'branches', 'warehouses']);

            return response()->json([
                'success' => true,
                'message' => "Sister concern '{$company->name}' created successfully.",
                'company' => $company,
            ]);
        }

        if ($request->input('redirect_to') === 'sister-concerns') {
            return $this->flashSuccess("Sister concern '{$company->name}' created successfully.", 'organization.sister-concerns.companies');
        }

        return $this->flashSuccess("Company '{$company->name}' created successfully.", 'organization.companies.index');
    }

    public function show(Request $request, Company $company): View|JsonResponse
    {
        $company->load(['businessGroup', 'branches', 'warehouses', 'financialYears']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'company' => $company,
            ]);
        }

        return view('organization.companies.show', [
            'company' => $company,
        ]);
    }

    public function edit(Request $request, Company $company): View|JsonResponse
    {
        $company->load(['businessGroup', 'branches', 'warehouses']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'company' => $company,
            ]);
        }

        return view('organization.companies.form', [
            'item' => $company,
            'businessGroups' => BusinessGroup::where('is_active', true)->orderBy('name')->get(),
            'states' => IndianStates::names(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse|JsonResponse
    {
        $data = $this->validated($request, $company);

        if ($company->is_default && empty($data['is_active'])) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate the default company. Please set another active company as default before deactivating this one.',
                ], 422);
            }

            return back()->withInput()->with('error', 'Cannot deactivate the default company. Please set another active company as default before deactivating this one.');
        }

        $company->update($data);

        if ($company->business_group_id) {
            $company->businessGroup?->companies()->syncWithoutDetaching([$company->id]);
        }

        if ($request->wantsJson()) {
            $company->load(['businessGroup', 'branches', 'warehouses']);

            return response()->json([
                'success' => true,
                'message' => "Sister concern '{$company->name}' updated successfully.",
                'company' => $company,
            ]);
        }

        if ($request->input('redirect_to') === 'sister-concerns') {
            return $this->flashSuccess("Sister concern '{$company->name}' updated successfully.", 'organization.sister-concerns.companies');
        }

        return $this->flashSuccess("Company '{$company->name}' updated successfully.", 'organization.companies.index');
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

        Company::where('is_default', true)->where('id', '!=', $company->id)->update(['is_default' => false]);
        $company->update(['is_default' => true]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$company->name} set as default company.",
            ]);
        }

        return back()->with('success', "{$company->name} set as default company.");
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
        // Normalize strings before validation
        if ($request->has('gstin') && filled($request->input('gstin'))) {
            $request->merge(['gstin' => strtoupper(trim((string) $request->input('gstin')))]);
        }
        if ($request->has('pan') && filled($request->input('pan'))) {
            $request->merge(['pan' => strtoupper(trim((string) $request->input('pan')))]);
        }
        if ($request->has('code') && filled($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }
        if ($request->filled('cin_number') && ! $request->filled('cin')) {
            $request->merge(['cin' => trim((string) $request->input('cin_number'))]);
        }
        if ($request->filled('msme_reg_no') && ! $request->filled('msme_registration_no')) {
            $request->merge(['msme_registration_no' => trim((string) $request->input('msme_reg_no'))]);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:companies,code' . ($company ? ',' . $company->id : ''),
            'legal_name' => 'nullable|string|max:255',
            'business_group_id' => 'nullable|exists:business_groups,id',

            // Section 1: Company Information
            'company_type' => 'required|string|max:50',
            'incorporation_no' => 'nullable|string|max:50',
            'incorporation_date' => 'nullable|date',
            'cin' => 'nullable|string|max:30',
            'cin_number' => 'nullable|string|max:30',
            'logo_emoji' => 'nullable|string|max:20',

            // Section 2: Tax & Regulatory
            'gstin' => ['required', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
            'pan' => ['required', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
            'tan' => 'nullable|string|max:20',
            'iec_code' => 'nullable|string|max:30',
            'msme_registration_no' => 'nullable|string|max:50',
            'msme_reg_no' => 'nullable|string|max:50',
            'udyam_registration_no' => 'nullable|string|max:50',
            'msme_category' => 'nullable|in:none,micro,small,medium',

            // Section 3: Address & Contact
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',

            // Section 4: Business & Financial
            'nature_of_business' => 'nullable|string|max:255',
            'authorized_capital' => 'nullable|numeric|min:0',
            'paid_up_capital' => 'nullable|numeric|min:0',
            'annual_turnover' => 'nullable|numeric|min:0',
            'financial_year' => 'nullable|string|max:30',
            'accounting_software' => 'nullable|string|max:50',
            'branches_count_manual' => 'nullable|integer|min:0',
            'employees_count_manual' => 'nullable|integer|min:0',

            // Section 5: Relationship & Status
            'relationship_type' => 'nullable|string|max:50',
            'shareholding_percentage' => 'nullable|numeric|min:0|max:100',
            'compliance_status' => 'nullable|string|max:50',
            'is_active' => 'nullable',

            // Existing bank and settings
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'purchase_terms_and_conditions' => 'nullable|string',
            'selling_terms_and_conditions' => 'nullable|string',
            'due_date_basis' => 'nullable|in:invoice_date,inward_date',
            'logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_logo' => 'nullable|boolean',
        ];

        $messages = [
            'name.required' => 'Company Name is required.',
            'code.unique' => 'This Company Code has already been taken.',
            'company_type.required' => 'Company Type is required.',
            'gstin.required' => 'GSTIN is required.',
            'gstin.size' => 'GSTIN must be exactly 15 characters.',
            'gstin.regex' => 'The GSTIN format is invalid. It must be a valid 15-character GSTIN (e.g. 27ABCDE1234F1Z5).',
            'pan.required' => 'PAN is required.',
            'pan.size' => 'PAN must be exactly 10 characters.',
            'pan.regex' => 'The PAN format is invalid. It must be a valid 10-character alphanumeric PAN (e.g. ABCDE1234F).',
            'email.email' => 'Please provide a valid email address.',
            'shareholding_percentage.min' => 'Shareholding percentage must be at least 0%.',
            'shareholding_percentage.max' => 'Shareholding percentage cannot exceed 100%.',
            'branches_count_manual.integer' => 'Branches must be a valid whole number.',
            'branches_count_manual.min' => 'Branches cannot be negative.',
            'employees_count_manual.integer' => 'Employees must be a valid whole number.',
            'employees_count_manual.min' => 'Employees cannot be negative.',
            'authorized_capital.min' => 'Authorized Capital cannot be negative.',
            'paid_up_capital.min' => 'Paid-up Capital cannot be negative.',
            'annual_turnover.min' => 'Annual Turnover cannot be negative.',
            'logo.file' => 'The uploaded logo must be a valid file.',
            'logo.mimes' => 'The company logo must be a file of type: PNG, JPG, JPEG, WEBP, or SVG.',
            'logo.max' => 'The company logo size must not exceed 2 MB.',
        ];

        $data = $request->validate($rules, $messages);

        // Auto-generate code if not entered
        if (empty($data['code'])) {
            $data['code'] = CodeGenerator::forCompany();
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['due_date_basis'] = $data['due_date_basis'] ?? ($company?->due_date_basis ?? 'invoice_date');
        $data['msme_category'] = $data['msme_category'] ?? 'none';
        $data['country'] = $data['country'] ?? 'India';
        $data['relationship_type'] = $data['relationship_type'] ?? 'Sister Concern';
        $data['compliance_status'] = $data['compliance_status'] ?? 'Compliant';
        $data['logo_emoji'] = $data['logo_emoji'] ?? '🏢';

        if (! empty($data['cin_number']) && empty($data['cin'])) {
            $data['cin'] = $data['cin_number'];
        }
        if (! empty($data['msme_reg_no']) && empty($data['msme_registration_no'])) {
            $data['msme_registration_no'] = $data['msme_reg_no'];
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            Storage::disk('public')->makeDirectory('companies/logos');
            $path = $file->store('companies/logos', 'public');

            if ($path === false) {
                \Illuminate\Support\Facades\Log::error('Failed to store company logo to public disk', [
                    'company_id' => $company?->id ?? null,
                    'original_name' => $file->getClientOriginalName(),
                ]);

                if ($request->wantsJson()) {
                    abort(422, 'Failed to save the uploaded logo.');
                }

                return back()->withInput()->withErrors([
                    'logo' => 'Failed to save the uploaded logo to server storage. Please check disk permissions.',
                ]);
            }

            if ($company?->logo_path && $company->logo_path !== $path) {
                $cleanPath = $company->getCleanLogoPath();
                if ($cleanPath && Storage::disk('public')->exists($cleanPath)) {
                    Storage::disk('public')->delete($cleanPath);
                }
            }

            $data['logo_path'] = $path;
        } elseif ($request->boolean('remove_logo') && $company?->logo_path) {
            $cleanPath = $company->getCleanLogoPath();
            if ($cleanPath && Storage::disk('public')->exists($cleanPath)) {
                Storage::disk('public')->delete($cleanPath);
            }
            $data['logo_path'] = null;
        }

        return $data;
    }
}