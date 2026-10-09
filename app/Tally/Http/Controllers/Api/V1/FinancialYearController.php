<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Http\Requests\StoreFinancialYearRequest;
use Tally\Http\Requests\UpdateFinancialYearRequest;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialYearController extends ApiController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $query = $company->financialYears()->with('integrationReference')->orderByDesc('start_date');

        return $this->page($query->paginate($this->perPage($request)), fn (FinancialYear $year) => $this->transform($year));
    }

    public function store(StoreFinancialYearRequest $request, Company $company): JsonResponse
    {
        $year = $company->financialYears()->create($request->validated());
        $this->rememberReference($request, $year, $company);

        return $this->data($this->transform($year), 201);
    }

    public function show(Company $company, FinancialYear $financialYear): JsonResponse
    {
        $financialYear->load('integrationReference');

        return $this->data($this->transform($financialYear));
    }

    public function update(UpdateFinancialYearRequest $request, Company $company, FinancialYear $financialYear): JsonResponse
    {
        $financialYear->update($request->validated());
        $this->rememberReference($request, $financialYear, $company);

        return $this->data($this->transform($financialYear));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(FinancialYear $year): array
    {
        return $this->withIntegration($year, [
            'id' => $year->id,
            'company_id' => $year->company_id,
            'name' => $year->name,
            'start_date' => $year->start_date?->toDateString(),
            'end_date' => $year->end_date?->toDateString(),
            'is_active' => $year->is_active,
        ]);
    }
}
