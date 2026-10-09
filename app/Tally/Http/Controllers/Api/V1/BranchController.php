<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Http\Requests\StoreBranchRequest;
use Tally\Http\Requests\UpdateBranchRequest;
use Tally\Models\Branch;
use Tally\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends ApiController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $query = $company->branches()->with('integrationReference')->orderBy('name');
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
            });
        }

        return $this->page($query->paginate($this->perPage($request)), fn (Branch $branch) => $this->transform($branch));
    }

    public function store(StoreBranchRequest $request, Company $company): JsonResponse
    {
        $branch = $company->branches()->create($request->validated());
        $this->rememberReference($request, $branch, $company);

        return $this->data($this->transform($branch), 201);
    }

    public function show(Company $company, Branch $branch): JsonResponse
    {
        $branch->load('integrationReference');

        return $this->data($this->transform($branch));
    }

    public function update(UpdateBranchRequest $request, Company $company, Branch $branch): JsonResponse
    {
        $branch->update($request->validated());
        $this->rememberReference($request, $branch, $company);

        return $this->data($this->transform($branch));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Branch $branch): array
    {
        return $this->withIntegration($branch, [
            'id' => $branch->id,
            'company_id' => $branch->company_id,
            'name' => $branch->name,
            'code' => $branch->code,
            'city' => $branch->city,
            'state' => $branch->state,
            'is_active' => $branch->is_active,
        ]);
    }
}
