<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
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
        $type = $this->booksTypeForCustomer($customer, $party);
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
        $partyName = $party->ledger?->name ?: $party->legal_name ?: ('Party '.$party->id);
        // Prefer existing DMS party imported by TallyInbound::dmsCustomer (same company+name).
        if (! $customer) {
            $customer = Customer::query()
                ->where('company_id', $organizationId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($partyName)])
                ->first();
        }

        $typeId = $customer?->customer_type_id ?: CustomerType::query()->orderBy('id')->value('id');
        if (! $typeId) {
            $typeId = CustomerType::query()->create([
                'name' => 'General',
                'code' => 'GENERAL',
                'is_active' => true,
            ])->id;
        }

        $code = $customer?->code ?: SyncNames::unique(
            Customer::query()->where('company_id', $organizationId),
            'code',
            SyncNames::code($party->ledger?->code ?: ('P'.$party->id), 30),
            $customer?->id,
            30
        );
        if ($code === '') {
            $code = 'P'.$party->id;
        }

        // Preserve DMS dual-role "both" when Books only has customer|supplier.
        $partyType = $customer?->party_type === Customer::PARTY_TYPE_BOTH
            ? Customer::PARTY_TYPE_BOTH
            : ($party->type === 'supplier' ? Customer::PARTY_TYPE_SUNDRY_CREDITORS : Customer::PARTY_TYPE_SUNDRY_DEBTORS);

        $gstReg = $party->gst_registration_type?->label()
            ?? (is_string($party->gst_registration_type) ? $party->gst_registration_type : null);
        $gstReg = Customer::normalizeGstRegistrationType($gstReg);

        $attributes = [
            'company_id' => $organizationId,
            'customer_type_id' => $typeId,
            'name' => $partyName,
            'trade_name' => $party->ledger?->mailing_name ?: $party->legal_name,
            'code' => $code,
            'party_type' => $partyType,
            'phone' => $party->mobile ?: $party->phone,
            'email' => $party->email,
            'address' => $party->billing_address,
            'gstin' => $party->gstin,
            'pan' => $party->pan ?: $party->ledger?->pan,
            'gst_registration_type' => $gstReg,
            'state' => $party->state,
            'pincode' => $party->ledger?->pincode,
            'credit_limit' => $party->credit_limit,
            'credit_days' => $party->credit_days,
            'interest_rate' => $party->ledger?->calculate_interest ? $party->ledger?->interest_rate : null,
            'is_active' => (bool) $party->is_active,
        ];

        // Avoid wiping DMS-enriched fields when Books party omits values.
        $attributes = array_filter(
            $attributes,
            static fn ($value) => $value !== null && $value !== ''
        );

        $customer ? $customer->update($attributes) : $customer = Customer::query()->create($attributes);
        SyncLinks::store(self::KEY, $customer, $party);
    }

    /**
     * Books parties only support customer|supplier. Dual-role DMS parties keep their
     * existing Books link type when present; otherwise default to customer.
     */
    private function booksTypeForCustomer(Customer $customer, ?Party $party): string
    {
        if ($customer->party_type === Customer::PARTY_TYPE_SUNDRY_CREDITORS) {
            return 'supplier';
        }

        if ($customer->party_type === Customer::PARTY_TYPE_BOTH && $party?->type === 'supplier') {
            return 'supplier';
        }

        return 'customer';
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
                throw new InvalidArgumentException(
                    "GSTIN {$gstin} is already used by another Books ledger. Resolve the duplicate before syncing party {$customer->name}."
                );
            }
        }

        $customer->loadMissing(['addresses', 'contacts']);
        $billing = $customer->defaultBillingAddress();
        $address = $this->primaryAddressLine($customer, $billing);
        $state = trim((string) ($billing?->state ?: $customer->state ?: '')) ?: null;
        $pincode = trim((string) ($billing?->pincode ?: $customer->pincode ?: '')) ?: null;
        $contact = trim((string) (
            $customer->contacts->firstWhere('is_primary', true)?->name
            ?: $customer->contacts->first()?->name
            ?: ''
        )) ?: null;
        $interestRate = (float) ($customer->interest_rate ?? 0);

        return [
            'type' => $type,
            'name' => $name,
            'code' => $customer->code ? SyncNames::code($customer->code) : null,
            'mailing_name' => trim((string) ($customer->trade_name ?: $customer->name)) ?: null,
            'billing_address' => $address !== '' ? $address : null,
            'state' => $state,
            'pincode' => $pincode,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'contact_person' => $contact,
            'gstin' => $gstin,
            'pan' => $customer->pan,
            'gst_registration_type' => $gstin ? GstRegistrationType::Regular : GstRegistrationType::Unregistered,
            'credit_limit' => $customer->credit_limit,
            'credit_days' => $customer->credit_days,
            'calculate_interest' => $interestRate > 0,
            'interest_rate' => $interestRate > 0 ? $interestRate : null,
            'maintain_bill_wise' => true,
            'is_active' => (bool) $customer->is_active,
            'country' => 'India',
            'branch_id' => BooksCompany::branchId((int) $customer->company_id, $customer->branch_id ? (int) $customer->branch_id : null),
        ];
    }

    private function primaryAddressLine(Customer $customer, mixed $billing): string
    {
        if ($billing) {
            $line1 = trim((string) ($billing->address_line_1 ?? ''));
            $line2 = trim((string) ($billing->address_line_2 ?? ''));
            $city = trim((string) ($billing->city ?? ''));
            $parts = array_values(array_filter([$line1, $line2, $city], fn ($part) => $part !== ''));
            if ($parts !== []) {
                return implode(', ', $parts);
            }
        }

        return trim((string) ($customer->address ?: ''));
    }
}
