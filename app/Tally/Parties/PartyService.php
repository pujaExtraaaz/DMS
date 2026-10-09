<?php

namespace Tally\Parties;

use Tally\Accounting\OpeningBalanceType;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Models\Party;
use Tally\Tax\GstRegistrationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Company $company, array $data, ?Party $party = null): Party
    {
        return DB::transaction(function () use ($company, $data, $party) {
            $type = $data['type'] === 'supplier' ? 'supplier' : 'customer';
            $group = AccountGroup::query()
                ->where('company_id', $company->id)
                ->where('code', $type === 'customer' ? 'DEBTORS' : 'CREDITORS')
                ->first();

            if (! $group) {
                throw ValidationException::withMessages([
                    'type' => 'The '.$type.' account group is not available for this company.',
                ]);
            }

            $ledgerData = [
                'company_id' => $company->id,
                'account_group_id' => $group->id,
                'name' => $data['name'],
                'code' => ($data['code'] ?? null) ?: null,
                'opening_balance' => $data['opening_balance'] ?? '0.00',
                'opening_balance_type' => $data['opening_balance_type'] ?? OpeningBalanceType::Debit,
                'address' => ($data['billing_address'] ?? null) ?: null,
                'state' => ($data['state'] ?? null) ?: null,
            'phone' => ($data['phone'] ?? null) ?: null,
            'email' => ($data['email'] ?? null) ?: null,
            'gstin' => ($data['gstin'] ?? null) ?: null,
            'gst_registration_type' => ($data['gst_registration_type'] ?? null) ?: null,
            'pan' => ($data['pan'] ?? null) ?: null,
                'credit_limit' => ($data['credit_limit'] ?? null) !== '' && ($data['credit_limit'] ?? null) !== null ? $data['credit_limit'] : null,
                'credit_days' => ($data['credit_days'] ?? null) !== '' && ($data['credit_days'] ?? null) !== null ? $data['credit_days'] : null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_system' => false,
            ];

            if ($party) {
                if ($party->ledger->hasAccountingEntries() && $party->type !== $type) {
                    throw ValidationException::withMessages([
                        'type' => 'The party type cannot change after the ledger has an opening balance or transactions.',
                    ]);
                }

                $party->ledger->update($ledgerData);
                $ledger = $party->ledger;
            } else {
                $ledger = Ledger::query()->create($ledgerData);
            }

            $profile = [
                'company_id' => $company->id,
                'ledger_id' => $ledger->id,
                'branch_id' => ($data['branch_id'] ?? null) ?: null,
                'type' => $type,
                'legal_name' => ($data['legal_name'] ?? null) ?: null,
                'contact_person' => ($data['contact_person'] ?? null) ?: null,
                'phone' => $ledgerData['phone'],
                'mobile' => ($data['mobile'] ?? null) ?: null,
                'email' => $ledgerData['email'],
                'billing_address' => $ledgerData['address'],
                'shipping_address' => ($data['shipping_address'] ?? null) ?: null,
                'state' => ($data['state'] ?? null) ?: null,
                'country' => ($data['country'] ?? null) ?: null,
                'gstin' => $ledgerData['gstin'],
                'pan' => $ledgerData['pan'],
                'gst_registration_type' => $ledgerData['gst_registration_type'],
                'credit_limit' => $ledgerData['credit_limit'],
                'credit_days' => $ledgerData['credit_days'],
                'is_active' => $ledgerData['is_active'],
            ];

            $year = app(\Tally\Context\WorkspaceContext::class)->financialYear();

            if ($year) {
                app(\Tally\Accounting\LedgerOpeningBook::class)->remember($ledger, $year);
            }

            $existing = Party::query()->where('ledger_id', $ledger->id)->first();

            if ($party || $existing) {
                ($party ?? $existing)->update($profile);

                return ($party ?? $existing)->fresh(['ledger', 'branch']);
            }

            return Party::query()->create($profile)->load(['ledger', 'branch']);
        });
    }

    public function setActive(Party $party, bool $active): void
    {
        DB::transaction(function () use ($party, $active): void {
            $party->update(['is_active' => $active]);
            $party->ledger->update(['is_active' => $active]);
        });
    }

    public function delete(Party $party): void
    {
        if (! $party->ledger->canBeDeleted()) {
            throw ValidationException::withMessages([
                'party' => 'This party has an opening balance or accounting history and cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($party): void {
            $ledger = $party->ledger;
            $party->delete();
            $ledger->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function fromLedger(Ledger $ledger, string $type, array $row = []): Party
    {
        return Party::query()->firstOrCreate(
            ['ledger_id' => $ledger->id],
            [
                'company_id' => $ledger->company_id,
                'type' => $type,
                'legal_name' => $row['legal_name'] ?? null,
                'contact_person' => $row['contact_person'] ?? null,
                'phone' => $ledger->phone,
                'mobile' => $row['mobile'] ?? null,
                'email' => $ledger->email,
                'billing_address' => $ledger->address,
                'shipping_address' => $row['shipping_address'] ?? null,
                'state' => $ledger->state,
                'country' => $row['country'] ?? null,
                'gstin' => $ledger->gstin,
                'pan' => $ledger->pan,
                'gst_registration_type' => $ledger->gst_registration_type instanceof GstRegistrationType
                    ? $ledger->gst_registration_type
                    : ($ledger->gst_registration_type ?: null),
                'credit_limit' => $ledger->credit_limit,
                'credit_days' => $ledger->credit_days,
                'is_active' => $ledger->is_active,
            ],
        );
    }
}
