<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Company;
use Tally\Models\Party;
use Tally\Parties\PartyService;
use Tally\Tax\GstRegistrationType;

class CustomerSync
{
    public const KEY = 'customer';

    public function __construct(private readonly PartyService $parties) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Customer) {
            $this->push($model);

            return;
        }

        if ($model instanceof Party) {
            $this->pull($model);
        }
    }

    public function push(Customer $customer): void
    {
        if (! $customer->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $customer->company_id);
        if (! $company) {
            return;
        }

        $party = Party::query()->find(SyncLinks::acctId(self::KEY, $customer));
        $type = $customer->party_type === Customer::PARTY_TYPE_SUNDRY_CREDITORS ? 'supplier' : 'customer';
        $saved = $this->parties->save($company, $this->payload($company, $customer, $type, $party), $party);
        SyncLinks::store(self::KEY, $customer, $saved);
    }

    public function pull(Party $party): void
    {
        $organizationId = BooksCompany::organizationId($party->company_id);
        if (! $organizationId) {
            return;
        }

        $party->loadMissing('ledger');
        $customer = Customer::query()->find(SyncLinks::dmsId(self::KEY, $party));
        $typeId = $customer?->customer_type_id ?: CustomerType::query()->orderBy('id')->value('id');
        if (! $typeId) {
            $typeId = CustomerType::query()->create(['name' => 'General', 'is_active' => true])->id;
        }

        $code = SyncNames::unique(
            Customer::query(),
            'code',
            SyncNames::code($party->ledger?->code ?: ('P'.$party->id), 30),
            $customer?->id,
            30
        );

        $attributes = [
            'company_id' => $organizationId,
            'customer_type_id' => $typeId,
            'name' => $party->ledger?->name ?: $party->legal_name ?: ('Party '.$party->id),
            'code' => $code,
            'party_type' => $party->type === 'supplier' ? Customer::PARTY_TYPE_SUNDRY_CREDITORS : Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'phone' => $party->phone,
            'email' => $party->email,
            'address' => $party->billing_address,
            'gstin' => $party->gstin,
            'state' => $party->state,
            'credit_limit' => $party->credit_limit,
            'credit_days' => $party->credit_days,
            'is_active' => (bool) $party->is_active,
        ];

        $customer ? $customer->update($attributes) : $customer = Customer::query()->create($attributes);
        SyncLinks::store(self::KEY, $customer, $party);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Company $company, Customer $customer, string $type, ?Party $party): array
    {
        $name = SyncNames::unique(
            \Tally\Models\Ledger::query()->where('company_id', $company->id),
            'name',
            $customer->name,
            $party?->ledger_id
        );

        $gstin = $customer->gstin ?: null;
        if ($gstin) {
            $taken = \Tally\Models\Ledger::query()
                ->where('company_id', $company->id)
                ->where('gstin', $gstin)
                ->when($party?->ledger_id, fn ($query) => $query->whereKeyNot($party->ledger_id))
                ->exists();
            if ($taken) {
                $gstin = null;
            }
        }

        return [
            'type' => $type,
            'name' => $name,
            'code' => $customer->code ? SyncNames::code($customer->code) : null,
            'billing_address' => $customer->address,
            'state' => $customer->state,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'gstin' => $gstin,
            'gst_registration_type' => $gstin ? GstRegistrationType::Regular : GstRegistrationType::Unregistered,
            'credit_limit' => $customer->credit_limit,
            'credit_days' => $customer->credit_days,
            'is_active' => (bool) $customer->is_active,
            'country' => 'India',
            'branch_id' => BooksCompany::branchId((int) $customer->company_id, $customer->branch_id ? (int) $customer->branch_id : null),
        ];
    }
}
