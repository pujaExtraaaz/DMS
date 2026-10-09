<?php

namespace Tally\Banking;

use Tally\Models\BankAccount;
use Tally\Models\Company;
use Tally\Models\Ledger;

class BankAccountService
{
    /**
     * @param  array{bank_name: ?string, account_number: ?string, ifsc: ?string}  $details
     */
    public function sync(Ledger $ledger, array $details): void
    {
        $ledger->loadMissing('accountGroup.parent');

        if (! $ledger->isBank() || blank($details['bank_name'] ?? null)) {
            $ledger->bankAccount()?->delete();

            return;
        }

        BankAccount::query()->updateOrCreate(
            ['ledger_id' => $ledger->id],
            [
                'company_id' => $ledger->company_id,
                'bank_name' => $details['bank_name'],
                'account_number' => $details['account_number'],
                'ifsc' => $details['ifsc'],
            ],
        );
    }

    /**
     * @return list<int>
     */
    public function bankGroupIds(Company $company): array
    {
        $groups = $company->accountGroups()->get()->keyBy('id');
        $ids = [];

        foreach ($groups as $group) {
            $id = $group->id;
            $guard = 0;

            while ($id && $guard < 50) {
                $current = $groups->get($id);

                if (! $current) {
                    break;
                }

                if ($current->code === 'BANK') {
                    $ids[] = $group->id;

                    break;
                }

                $id = $current->parent_id;
                $guard++;
            }
        }

        return $ids;
    }

    public function isBankGroup(Company $company, int $groupId): bool
    {
        return in_array($groupId, $this->bankGroupIds($company), true);
    }
}
