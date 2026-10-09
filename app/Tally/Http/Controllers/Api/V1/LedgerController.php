<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Banking\BankAccountService;
use Tally\Http\Requests\StoreLedgerRequest;
use Tally\Http\Requests\UpdateLedgerRequest;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LedgerController extends ApiController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $query = $company->ledgers()->with(['accountGroup', 'integrationReference'])->orderBy('name');
        $this->search($request, $query);

        if ($request->filled('account_group_id')) {
            $query->where('account_group_id', $request->integer('account_group_id'));
        }

        if ($request->filled('source') && $request->filled('external_reference_id')) {
            $query->whereHas('integrationReference', function ($query) use ($request) {
                $query->where('source', $request->string('source')->toString())
                    ->where('external_reference_id', $request->string('external_reference_id')->toString());
            });
        }

        return $this->page($query->paginate($this->perPage($request)), fn (Ledger $ledger) => $this->transform($ledger));
    }

    public function store(StoreLedgerRequest $request, Company $company): JsonResponse
    {
        $data = $request->validated();
        $ledger = $company->ledgers()->create(Arr::except($data, ['bank_name', 'account_number', 'ifsc', 'role']));
        app(BankAccountService::class)->sync($ledger, Arr::only($data, ['bank_name', 'account_number', 'ifsc']));
        $this->rememberReference($request, $ledger, $company);

        return $this->data($this->transform($ledger->load('accountGroup')), 201);
    }

    public function show(Company $company, Ledger $ledger): JsonResponse
    {
        $ledger->load(['accountGroup.parent', 'bankAccount', 'integrationReference']);

        return $this->data($this->transform($ledger));
    }

    public function update(UpdateLedgerRequest $request, Company $company, Ledger $ledger): JsonResponse
    {
        $data = $request->validated();
        $ledger->update(Arr::except($data, ['bank_name', 'account_number', 'ifsc', 'role']));
        app(BankAccountService::class)->sync($ledger, Arr::only($data, ['bank_name', 'account_number', 'ifsc']));
        $this->rememberReference($request, $ledger, $company);

        return $this->data($this->transform($ledger->load('accountGroup')));
    }

    public function destroy(Company $company, Ledger $ledger): JsonResponse
    {
        if (! $ledger->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->denyJson($ledger, 'This ledger cannot be deleted because accounting entries depend on it.');
        }

        $ledger->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(Ledger $ledger): array
    {
        return $this->withIntegration($ledger, [
            'id' => $ledger->id,
            'company_id' => $ledger->company_id,
            'account_group_id' => $ledger->account_group_id,
            'name' => $ledger->name,
            'code' => $ledger->code,
            'opening_balance' => (string) $ledger->opening_balance,
            'opening_balance_type' => $ledger->opening_balance_type->value,
            'gstin' => $ledger->gstin,
            'is_active' => $ledger->is_active,
            'is_system' => $ledger->is_system,
            'is_customer' => $ledger->isCustomer(),
            'is_supplier' => $ledger->isSupplier(),
        ]);
    }

    private function search(Request $request, $query): void
    {
        $search = trim($request->string('q')->toString());

        if ($search === '') {
            return;
        }

        $like = '%'.addcslashes($search, '%_\\').'%';
        $query->where(function ($query) use ($like) {
            $query->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
