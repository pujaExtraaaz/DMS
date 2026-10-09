<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Http\Requests\StoreAccountGroupRequest;
use Tally\Http\Requests\UpdateAccountGroupRequest;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountGroupController extends ApiController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $query = $company->accountGroups()->with(['parent', 'integrationReference'])->orderBy('name');
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
            });
        }

        return $this->page($query->paginate($this->perPage($request)), fn (AccountGroup $group) => $this->transform($group));
    }

    public function store(StoreAccountGroupRequest $request, Company $company): JsonResponse
    {
        $group = $company->accountGroups()->create($request->validated());
        $this->rememberReference($request, $group, $company);

        return $this->data($this->transform($group), 201);
    }

    public function show(Company $company, AccountGroup $accountGroup): JsonResponse
    {
        $accountGroup->load(['parent', 'integrationReference']);

        return $this->data($this->transform($accountGroup));
    }

    public function update(UpdateAccountGroupRequest $request, Company $company, AccountGroup $accountGroup): JsonResponse
    {
        $accountGroup->update($request->validated());
        $this->rememberReference($request, $accountGroup, $company);

        return $this->data($this->transform($accountGroup));
    }

    public function destroy(Company $company, AccountGroup $accountGroup): JsonResponse
    {
        if ($accountGroup->is_system) {
            return app(\Tally\Audit\AuditLogger::class)->denyJson($accountGroup, 'System groups cannot be deleted.');
        }

        if (! $accountGroup->canBeDeleted()) {
            return response()->json(['message' => 'Delete the ledgers and subgroups in this group first.'], 422);
        }

        $accountGroup->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(AccountGroup $group): array
    {
        return $this->withIntegration($group, [
            'id' => $group->id,
            'company_id' => $group->company_id,
            'parent_id' => $group->parent_id,
            'name' => $group->name,
            'code' => $group->code,
            'nature' => $group->nature->value,
            'is_system' => $group->is_system,
            'is_active' => $group->is_active,
        ]);
    }
}
