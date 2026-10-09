<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Http\Requests\PartyLedgerRequest;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends LedgerController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $role = $request->string('role')->toString();
        $groupIds = $this->partyGroupIds($company, $role);
        $query = $company->ledgers()
            ->with(['accountGroup.parent', 'integrationReference'])
            ->whereIn('account_group_id', $groupIds === [] ? [0] : $groupIds)
            ->orderBy('name');

        return $this->page($query->paginate($this->perPage($request)), fn (Ledger $ledger) => $this->party($ledger));
    }

    public function storeParty(PartyLedgerRequest $request, Company $company): JsonResponse
    {
        return parent::store($request, $company);
    }

    /**
     * @return list<int>
     */
    private function partyGroupIds(Company $company, string $role): array
    {
        $codes = match ($role) {
            'customer' => ['DEBTORS'],
            'supplier' => ['CREDITORS'],
            default => ['DEBTORS', 'CREDITORS'],
        };
        $groups = AccountGroup::query()->where('company_id', $company->id)->get()->keyBy('id');
        $ids = [];

        foreach ($groups as $group) {
            $cursor = $group;
            $guard = 0;

            while ($cursor && $guard < 50) {
                if (in_array($cursor->code, $codes, true)) {
                    $ids[] = $group->id;
                    break;
                }

                $cursor = $cursor->parent_id ? $groups->get($cursor->parent_id) : null;
                $guard++;
            }
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function party(Ledger $ledger): array
    {
        $payload = $this->transformParty($ledger);
        $payload['role'] = $ledger->isCustomer() ? 'customer' : 'supplier';

        return $payload;
    }

    /**
     * The parent transform is private, so parties build the same fields here.
     *
     * @return array<string, mixed>
     */
    private function transformParty(Ledger $ledger): array
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
        ]);
    }
}
