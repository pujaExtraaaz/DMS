<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Crm\Models\Lead;
use App\Domains\Master\Models\Customer;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Party;
use Tally\Parties\PartyService;
use Tally\Tax\GstRegistrationType;

/** CRM lead → Books Party (customer). Converted leads reuse CustomerSync. */
class LeadSync
{
    public const KEY = 'lead';

    public function __construct(
        private readonly PartyService $parties,
        private readonly CustomerSync $customers,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Lead) {
            $this->push($model);
        }
    }

    public function push(Lead $lead): void
    {
        if (! $lead->company_id) {
            return;
        }

        if ($lead->converted_customer_id) {
            $customer = Customer::query()->find($lead->converted_customer_id);
            if ($customer) {
                $this->customers->push($customer);
                $party = Party::query()->find(SyncLinks::acctId(CustomerSync::KEY, $customer));
                if ($party) {
                    SyncLinks::store(self::KEY, $lead, $party);
                }
            }

            return;
        }

        $company = BooksCompany::forOrganization((int) $lead->company_id);
        if (! $company) {
            return;
        }

        $party = Party::query()->find(SyncLinks::acctId(self::KEY, $lead));
        $name = $lead->name ?: $lead->company_name ?: ('Lead '.$lead->id);
        $payload = [
            'type' => 'customer',
            'name' => $name,
            'code' => 'LEAD'.$lead->id,
            'phone' => $lead->mobile ?: $lead->phone,
            'email' => $lead->email,
            'state' => $lead->state,
            'gst_registration_type' => GstRegistrationType::Unregistered,
            'billing_address' => trim(implode(', ', array_filter([
                $lead->street, $lead->city, $lead->state, $lead->zip,
            ]))) ?: null,
            'is_active' => ! in_array($lead->status, ['lost', 'junk', 'converted'], true),
            'country' => 'India',
            'branch_id' => BooksCompany::branchId((int) $lead->company_id, $lead->branch_id ? (int) $lead->branch_id : null),
        ];

        $saved = $this->parties->save($company, $payload, $party);
        SyncLinks::store(self::KEY, $lead, $saved);
    }
}
