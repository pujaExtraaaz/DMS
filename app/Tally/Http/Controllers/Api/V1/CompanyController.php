<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Authorization\CompanyAccess;
use Tally\Http\Requests\StoreCompanyRequest;
use Tally\Http\Requests\UpdateCompanyRequest;
use Tally\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $ids = CompanyAccess::ids($request->user());
        $query = Company::query()->with('integrationReference')->orderBy('name')
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids === [] ? [0] : $ids));
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
        }

        return $this->page($query->paginate($this->perPage($request)), fn (Company $company) => $this->transform($company));
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::provision($request->validated());
        $this->rememberReference($request, $company, $company);

        return $this->data($this->transform($company->load('financialYears', 'integrationReference')), 201);
    }

    public function show(Company $company): JsonResponse
    {
        $company->load(['branches', 'financialYears', 'integrationReference']);

        return $this->data($this->transform($company));
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());
        $this->rememberReference($request, $company, $company);

        return $this->data($this->transform($company));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Company $company): array
    {
        return $this->withIntegration($company, [
            'id' => $company->id,
            'name' => $company->name,
            'legal_name' => $company->legal_name,
            'gstin' => $company->gstin,
            'pan' => $company->pan,
            'state' => $company->state,
            'country' => $company->country,
            'is_active' => $company->is_active,
            'financial_year_start' => $company->financial_year_start?->toDateString(),
            'financial_year_end' => $company->financial_year_end?->toDateString(),
        ]);
    }
}
