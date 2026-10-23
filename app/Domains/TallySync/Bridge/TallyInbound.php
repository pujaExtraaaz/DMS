<?php

namespace App\Domains\TallySync\Bridge;

use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncRegistry;
use App\Domains\Tally\Observers\TallyAutoEnqueueObserver;
use App\Domains\TallySync\Queue\BooksSyncQueue;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\AccountNature;
use Tally\Accounting\OpeningBalanceType;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\AccountGroup;
use Tally\Models\BankAccount;
use Tally\Models\Company;
use Tally\Models\CostCategory;
use Tally\Models\CostCentre;
use Tally\Models\FinancialYear;
use Tally\Models\Godown;
use Tally\Models\Ledger;
use Tally\Models\Party;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\TallyLink;
use Tally\Models\Unit;
use Tally\Models\Voucher;
use Tally\Models\VoucherTypeMaster;
use Tally\Models\VoucherInventoryLine;
use Tally\Tax\GstRegistrationType;

class TallyInbound
{
    /** @var array<string, int> */
    private array $counts = ['created' => 0, 'updated' => 0, 'conflicts' => 0, 'skipped' => 0];

    private int $maxAlter = 0;

    private int $cursorStart = 0;

    /**
     * @return array{created:int, updated:int, conflicts:int, skipped:int, alter_id:int}
     */
    public function apply(string $key, string $xml): array
    {
        $this->counts = ['created' => 0, 'updated' => 0, 'conflicts' => 0, 'skipped' => 0];
        $this->cursorStart = (int) DB::table('tally_sync_cursors')->where('collection', $key)->value('alter_id');
        $this->maxAlter = $this->cursorStart;
        if (isset(TallyExportBuilder::REPORTS[$key])) {
            return $this->report($key, $xml);
        }

        $company = $this->company();
        if (! $company) {
            $this->conflict($key, null, null, 'Set TALLY_COMPANY to the exact Books company name before import.');

            return $this->finish($key);
        }

        $dom = new \DOMDocument;
        if (! @$dom->loadXML($xml)) {
            return $this->counts + ['alter_id' => $this->maxAlter];
        }

        $tag = match ($key) {
            'groups' => 'GROUP',
            'stock_groups' => 'STOCKGROUP',
            'units' => 'UNIT',
            'godowns' => 'GODOWN',
            'cost_categories' => 'COSTCATEGORY',
            'cost_centres' => 'COSTCENTRE',
            'ledgers', 'bank_accounts' => 'LEDGER',
            'voucher_types' => 'VOUCHERTYPE',
            'stock_items' => 'STOCKITEM',
            'vouchers' => 'VOUCHER',
            default => '',
        };

        if ($tag === '') {
            return $this->finish($key);
        }

        foreach ($dom->getElementsByTagName($tag) as $node) {
            if (! $node instanceof \DOMElement || $node->parentNode?->nodeName === $tag || $node->parentNode?->nodeName === 'CMPINFO') {
                continue;
            }
            $alter = (int) $this->text($node, 'ALTERID');
            $this->maxAlter = max($this->maxAlter, $alter);
            try {
                TallyAutoEnqueueObserver::withoutSync(function () use ($key, $node, $company) {
                    SyncGuard::run(function () use ($key, $node, $company) {
                        $this->one($key, $node, $company);
                    });
                });
            } catch (\Throwable $exception) {
                $this->conflict($key, $this->text($node, 'GUID'), $this->text($node, 'NAME') ?: $node->getAttribute('NAME'), $exception->getMessage());
            }
        }

        return $this->finish($key);
    }

    private function one(string $key, \DOMElement $node, Company $company): void
    {
        $guid = $this->text($node, 'GUID') ?: null;
        $name = $this->text($node, 'NAME') ?: $node->getAttribute('NAME');
        if ($name === '' && $key !== 'vouchers') {
            $this->counts['skipped']++;

            return;
        }

        $model = match ($key) {
            'groups' => $this->group($company, $node, $name),
            'stock_groups' => $this->named($company, ProductGroup::class, 'books_stock_group', $node, $name, ['parent_id' => $this->parentId(ProductGroup::class, $company, $this->text($node, 'PARENT'))]),
            'units' => $this->unit($company, $node, $name),
            'godowns' => $this->named($company, Godown::class, 'books_godown', $node, $name, [
                'parent_id' => $this->parentId(Godown::class, $company, $this->text($node, 'PARENT')),
                'address' => $this->text($node, 'ADDRESS') ?: null,
            ]),
            'cost_categories' => $this->named($company, CostCategory::class, 'books_cost_category', $node, $name, []),
            'cost_centres' => $this->costCentre($company, $node, $name),
            'ledgers', 'bank_accounts' => $this->ledger($company, $node, $name),
            'voucher_types' => $this->voucherTypeMaster($company, $node, $name),
            'stock_items' => $this->stockItem($company, $node, $name),
            'vouchers' => $this->voucher($company, $node),
            default => null,
        };

        if ($model instanceof Model) {
            $this->link($company, $model, $this->tallyType($key), $node);
            $this->pushToDms($model);
        }
    }

    private function group(Company $company, \DOMElement $node, string $name): AccountGroup
    {
        $revenue = $this->yes($this->text($node, 'ISREVENUE'));
        $deemed = $this->yes($this->text($node, 'ISDEEMEDPOSITIVE'));
        $nature = match (true) {
            $revenue && $deemed => AccountNature::Expense,
            $revenue && ! $deemed => AccountNature::Income,
            ! $revenue && $deemed => AccountNature::Asset,
            default => AccountNature::Liability,
        };

        return $this->named($company, AccountGroup::class, 'books_group', $node, $name, [
            'parent_id' => $this->parentId(AccountGroup::class, $company, $this->text($node, 'PARENT')),
            'nature' => $nature,
            'affects_gross_profit' => $this->yes($this->text($node, 'AFFECTSGROSSPROFIT')),
            'is_active' => true,
        ]);
    }

    private function unit(Company $company, \DOMElement $node, string $name): Unit
    {
        $original = $this->text($node, 'ORIGINALNAME') ?: $name;

        return $this->named($company, Unit::class, 'books_unit', $node, $name, [
            'name' => $original,
            'symbol' => $name,
            'decimal_places' => (int) ($this->text($node, 'DECIMALPLACES') ?: 0),
            'gst_uqc' => $this->text($node, 'REPORTINGUQC') ?: null,
            'is_active' => true,
        ], 'symbol');
    }

    private function costCentre(Company $company, \DOMElement $node, string $name): CostCentre
    {
        $categoryName = $this->text($node, 'CATEGORY') ?: 'Primary Cost Category';
        $category = CostCategory::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => $categoryName],
            ['is_active' => true]
        );

        return $this->named($company, CostCentre::class, 'books_cost_centre', $node, $name, [
            'cost_category_id' => $category->id,
            'parent_id' => $this->parentId(CostCentre::class, $company, $this->text($node, 'PARENT')),
            'is_active' => true,
        ]);
    }

    private function ledger(Company $company, \DOMElement $node, string $name): ?Ledger
    {
        $parentName = $this->text($node, 'PARENT') ?: 'Primary';
        $parent = AccountGroup::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => $parentName],
            ['nature' => AccountNature::Asset, 'is_active' => true, 'sort_order' => 0]
        );
        $opening = (float) str_replace(',', '', $this->text($node, 'OPENINGBALANCE'));
        $gst = strtolower($this->text($node, 'GSTREGISTRATIONTYPE'));
        $gstType = match (true) {
            str_contains($gst, 'composition') => GstRegistrationType::Composition,
            str_contains($gst, 'unregistered') => GstRegistrationType::Unregistered,
            str_contains($gst, 'consumer') => GstRegistrationType::Consumer,
            default => GstRegistrationType::Regular,
        };
        $ledger = $this->named($company, Ledger::class, 'books_ledger', $node, $name, [
            'account_group_id' => $parent->id,
            'mailing_name' => $this->text($node, 'MAILINGNAME') ?: $name,
            'address' => $this->text($node, 'ADDRESS') ?: null,
            'phone' => $this->text($node, 'LEDGERPHONE') ?: null,
            'email' => $this->text($node, 'EMAIL') ?: null,
            'contact_person' => $this->text($node, 'LEDGERCONTACT') ?: null,
            'gstin' => $this->text($node, 'PARTYGSTIN') ?: null,
            'gst_registration_type' => $gstType,
            'pan' => $this->text($node, 'INCOMETAXNUMBER') ?: null,
            'state' => $this->text($node, 'STATENAME') ?: null,
            'country' => $this->text($node, 'COUNTRYNAME') ?: null,
            'pincode' => $this->text($node, 'PINCODE') ?: null,
            'opening_balance' => abs($opening),
            'opening_balance_type' => $opening < 0 ? OpeningBalanceType::Debit : OpeningBalanceType::Credit,
            'maintain_bill_wise' => $this->yes($this->text($node, 'ISBILLWISEON')),
            'cost_centres_applicable' => $this->yes($this->text($node, 'ISCOSTCENTRESON')),
            'credit_limit' => (float) str_replace(',', '', $this->text($node, 'CREDITLIMIT')),
            'credit_days' => (int) $this->text($node, 'BILLCREDITPERIOD'),
            'calculate_interest' => $this->yes($this->text($node, 'ISINTERESTON')),
            'is_active' => true,
        ]);

        $this->bankAccount($ledger, $node);

        $parentLower = strtolower($parentName);
        if (str_contains($parentLower, 'debtor') || str_contains($parentLower, 'creditor')) {
            $party = Party::query()->firstOrNew(['company_id' => $company->id, 'ledger_id' => $ledger->id]);
            $party->fill([
                'type' => str_contains($parentLower, 'creditor') ? 'supplier' : 'customer',
                'legal_name' => $name,
                'phone' => $ledger->phone,
                'email' => $ledger->email,
                'billing_address' => $ledger->address,
                'state' => $ledger->state,
                'country' => $ledger->country,
                'gstin' => $ledger->gstin,
                'pan' => $ledger->pan,
                'gst_registration_type' => $gstType,
                'credit_limit' => $ledger->credit_limit,
                'credit_days' => $ledger->credit_days,
                'is_active' => true,
            ]);
            $party->save();
            $this->pushToDms($party);
        }

        return $ledger;
    }

    private function stockItem(Company $company, \DOMElement $node, string $name): Product
    {
        $unitName = $this->text($node, 'BASEUNITS') ?: 'Nos';
        $unit = Unit::query()->where('company_id', $company->id)->where(function ($query) use ($unitName) {
            $query->where('symbol', $unitName)->orWhere('name', $unitName);
        })->first();
        if (! $unit) {
            $unit = Unit::query()->create([
                'company_id' => $company->id,
                'name' => $unitName,
                'symbol' => $unitName,
                'decimal_places' => 2,
                'is_active' => true,
            ]);
        }
        $groupName = $this->text($node, 'PARENT');
        $groupId = null;
        if ($groupName !== '' && strcasecmp($groupName, 'Primary') !== 0) {
            $groupId = ProductGroup::query()->firstOrCreate(
                ['company_id' => $company->id, 'name' => $groupName],
                ['is_active' => true]
            )->id;
        }
        $opening = $this->leadingNumber($this->text($node, 'OPENINGBALANCE'));

        return $this->named($company, Product::class, 'books_stock_item', $node, $name, [
            'product_group_id' => $groupId,
            'primary_unit_id' => $unit->id,
            'description' => $this->text($node, 'DESCRIPTION') ?: null,
            'type_of_supply' => stripos($this->text($node, 'GSTTYPEOFSUPPLY'), 'service') !== false ? 'service' : 'goods',
            'opening_quantity' => $opening,
            'opening_rate' => $this->leadingNumber($this->text($node, 'OPENINGRATE')),
            'opening_value' => abs($this->leadingNumber($this->text($node, 'OPENINGVALUE'))),
            'purchase_rate' => $this->leadingNumber($this->text($node, 'STANDARDCOST')),
            'sales_rate' => $this->leadingNumber($this->text($node, 'STANDARDPRICE')),
            'track_batch' => $this->yes($this->text($node, 'ISBATCHWISEON')),
            'is_active' => true,
        ]);
    }

    private function voucher(Company $company, \DOMElement $node): ?Voucher
    {
        if ($this->yes($this->text($node, 'ISDELETED')) || $this->yes($this->text($node, 'ISCANCELLED'))) {
            $this->counts['skipped']++;

            return null;
        }

        $typeName = $this->text($node, 'VOUCHERTYPENAME') ?: $node->getAttribute('VCHTYPE');
        if ($typeName === '') {
            $this->counts['skipped']++;

            return null;
        }
        $type = $this->voucherType($typeName);
        if (! $type) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $typeName, 'Voucher type is not mapped.');
            return null;
        }

        $number = $this->text($node, 'VOUCHERNUMBER') ?: $node->getAttribute('VCHNUMBER');
        $date = $this->date($this->text($node, 'DATE'));
        $remote = $node->getAttribute('REMOTEID');
        $existing = $this->findVoucher($company, $node, $type, $number, $date);
        if ($existing && BooksSyncQueue::pendingFor($existing)) {
            $this->counts['skipped']++;

            return null;
        }
        if ($existing && $existing->status === VoucherStatus::Posted) {
            $link = $this->linkRow($existing, 'books_voucher');
            $localNewer = $link && $existing->updated_at && $link->updated_at && $existing->updated_at->gt($link->updated_at);
            if ($localNewer) {
                $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Books and Tally both changed '.$number.'. Neither side was overwritten.');
            }
            $this->counts['skipped']++;

            return $existing;
        }

        $entries = $this->voucherEntries($company, $node);
        if ($entries === []) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Voucher has no ledger lines that exist in Books.');
            return null;
        }

        $branch = $company->branches()->where('is_active', true)->orderBy('id')->first() ?: $company->branches()->orderBy('id')->first();
        $year = $date ? FinancialYear::query()->where('company_id', $company->id)->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first() : null;
        $user = $existing?->creator ?: User::query()->orderBy('id')->first();
        if (! $branch || ! $year || ! $user || ! $date) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Books needs a branch, an open financial year, and a user for this voucher.');
            return null;
        }

        $partyName = $this->text($node, 'PARTYLEDGERNAME');
        $party = $partyName !== '' ? Ledger::query()->where('company_id', $company->id)->where('name', $partyName)->first() : null;
        $saved = app(VoucherEngine::class)->save($company, $branch, $year, $user, $type, [
            'voucher_date' => $date,
            'voucher_number' => $number,
            'reference_number' => $this->text($node, 'REFERENCE') ?: null,
            'reference_date' => $this->date($this->text($node, 'REFERENCEDATE')) ?: null,
            'effective_date' => $this->date($this->text($node, 'EFFECTIVEDATE')) ?: $date,
            'place_of_supply' => $this->text($node, 'PLACEOFSUPPLY') ?: null,
            'narration' => $this->text($node, 'NARRATION') ?: null,
            'party_ledger_id' => $party?->id,
            'is_optional' => $this->yes($this->text($node, 'ISOPTIONAL')),
            'is_post_dated' => $this->yes($this->text($node, 'ISPOSTDATED')),
            'is_memo' => $this->yes($this->text($node, 'ISMEMO')),
            'is_invoice' => $this->yes($this->text($node, 'ISINVOICE')),
            'entries' => $entries,
        ], true, $existing && $existing->isDraft() ? $existing : null, $number !== '' ? $number : null);

        $this->inventoryLines($saved, $company, $node);

        return $saved;
    }

    /**
     * @param  class-string<Model>  $class
     * @param  array<string, mixed>  $values
     */
    private function named(Company $company, string $class, string $tallyType, \DOMElement $node, string $name, array $values, string $nameColumn = 'name'): Model
    {
        $existing = $this->findMaster($class, $tallyType, $node, $company, $name, $nameColumn);
        if ($existing && BooksSyncQueue::pendingFor($existing)) {
            $this->counts['skipped']++;

            return $existing;
        }
        if ($existing && $this->localNewer($existing, $tallyType)) {
            $this->conflict($tallyType, $this->text($node, 'GUID'), $name, 'Books and Tally both changed '.$name.'. Neither side was overwritten.');
            $this->counts['skipped']++;

            return $existing;
        }

        $values['company_id'] = $company->id;
        if ($nameColumn === 'name') {
            $values['name'] = $name;
        }
        if ($existing) {
            $existing->fill($values);
            $existing->save();
            $this->counts['updated']++;

            return $existing;
        }

        $created = $class::query()->create($values);
        $this->counts['created']++;

        return $created;
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function findMaster(string $class, string $tallyType, \DOMElement $node, Company $company, string $name, string $nameColumn): ?Model
    {
        $guid = $this->text($node, 'GUID');
        if ($guid !== '') {
            $link = TallyLink::query()->where('tally_type', $tallyType)->where('guid', $guid)->first();
            if ($link) {
                $found = $class::query()->find($link->linkable_id);
                if ($found) {
                    return $found;
                }
            }
        }

        return $class::query()->where('company_id', $company->id)->where($nameColumn, $name)->first();
    }

    private function findVoucher(Company $company, \DOMElement $node, VoucherType $type, string $number, ?string $date): ?Voucher
    {
        $remote = $node->getAttribute('REMOTEID');
        if (preg_match('/^books(?:_[a-z]+)?-[a-z_]+-(\d+)$/', $remote, $match) || preg_match('/^books_voucher-(\d+)$/', $remote, $match)) {
            $found = Voucher::query()->find((int) $match[1]);
            if ($found) {
                return $found;
            }
        }
        $guid = $this->text($node, 'GUID');
        if ($guid !== '') {
            $link = TallyLink::query()->where('tally_type', 'books_voucher')->where('guid', $guid)->first();
            if ($link) {
                $found = Voucher::query()->find($link->linkable_id);
                if ($found) {
                    return $found;
                }
            }
        }
        if ($number === '' || ! $date) {
            return null;
        }

        return Voucher::query()
            ->where('company_id', $company->id)
            ->where('voucher_type', $type->value)
            ->where('voucher_number', $number)
            ->whereDate('voucher_date', $date)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function voucherEntries(Company $company, \DOMElement $node): array
    {
        $rows = [];
        foreach (['ALLLEDGERENTRIES.LIST', 'LEDGERENTRIES.LIST'] as $tag) {
            foreach ($node->getElementsByTagName($tag) as $entry) {
                if (! $entry instanceof \DOMElement || $entry->parentNode !== $node) {
                    continue;
                }
                $ledgerName = $this->text($entry, 'LEDGERNAME');
                $ledger = Ledger::query()->where('company_id', $company->id)->where('name', $ledgerName)->first();
                if (! $ledger) {
                    continue;
                }
                $amount = (float) str_replace(',', '', $this->text($entry, 'AMOUNT'));
                $centreName = '';
                $centreNode = $entry->getElementsByTagName('COSTCENTREALLOCATIONS.LIST')->item(0);
                if ($centreNode instanceof \DOMElement) {
                    $centreName = $this->text($centreNode, 'NAME');
                }
                $centreId = $centreName !== '' ? CostCentre::query()->where('company_id', $company->id)->where('name', $centreName)->value('id') : null;
                $bank = $entry->getElementsByTagName('BANKALLOCATIONS.LIST')->item(0);
                $rows[] = [
                    'ledger_id' => $ledger->id,
                    'debit' => $amount < 0 ? abs($amount) : 0,
                    'credit' => $amount > 0 ? abs($amount) : 0,
                    'narration' => $this->text($entry, 'NARRATION') ?: null,
                    'cost_centre_id' => $centreId,
                    'bank_transaction_type' => $bank instanceof \DOMElement ? ($this->text($bank, 'TRANSACTIONTYPE') ?: null) : null,
                    'instrument_number' => $bank instanceof \DOMElement ? ($this->text($bank, 'INSTRUMENTNUMBER') ?: null) : null,
                    'instrument_date' => $bank instanceof \DOMElement ? $this->date($this->text($bank, 'INSTRUMENTDATE')) : null,
                    'payment_favouring' => $bank instanceof \DOMElement ? ($this->text($bank, 'PAYMENTFAVOURING') ?: null) : null,
                    'bank_name' => $bank instanceof \DOMElement ? ($this->text($bank, 'BANKNAME') ?: null) : null,
                    'bank_account_number' => $bank instanceof \DOMElement ? ($this->text($bank, 'ACCOUNTNUMBER') ?: null) : null,
                    'bank_ifsc' => $bank instanceof \DOMElement ? ($this->text($bank, 'IFSCODE') ?: null) : null,
                ];
            }
            if ($rows !== []) {
                break;
            }
        }

        return $rows;
    }

    private function inventoryLines(Voucher $voucher, Company $company, \DOMElement $node): void
    {
        $voucher->inventoryLines()->delete();
        $line = 0;
        foreach ($node->getElementsByTagName('ALLINVENTORYENTRIES.LIST') as $entry) {
            if (! $entry instanceof \DOMElement || $entry->parentNode !== $node) {
                continue;
            }
            $product = Product::query()->where('company_id', $company->id)->where('name', $this->text($entry, 'STOCKITEMNAME'))->first();
            $godown = Godown::query()->where('company_id', $company->id)->where('name', $this->text($entry, 'GODOWNNAME'))->first();
            if (! $product) {
                continue;
            }
            $line++;
            VoucherInventoryLine::query()->create([
                'voucher_id' => $voucher->id,
                'line_number' => $line,
                'product_id' => $product->id,
                'godown_id' => $godown?->id,
                'batch_name' => $this->text($entry, 'BATCHNAME') ?: null,
                'quantity' => abs($this->leadingNumber($this->text($entry, 'ACTUALQTY'))),
                'rate' => $this->leadingNumber($this->text($entry, 'RATE')),
                'amount' => abs((float) str_replace(',', '', $this->text($entry, 'AMOUNT'))),
                'discount_amount' => abs((float) str_replace(',', '', $this->text($entry, 'DISCOUNT'))),
            ]);
        }
    }

    private function link(Company $company, Model $model, string $type, \DOMElement $node): void
    {
        $guid = $this->text($node, 'GUID') ?: null;
        TallyLink::query()->updateOrCreate(
            ['linkable_type' => $model::class, 'linkable_id' => $model->getKey(), 'tally_type' => $type],
            [
                'company_id' => $company->id,
                'guid' => $guid,
                'alter_id' => $this->text($node, 'ALTERID') ?: null,
                'master_id' => $this->text($node, 'MASTERID') ?: null,
                'remote_id' => $node->getAttribute('REMOTEID') ?: ($type.'-'.$model->getKey()),
            ]
        );
    }

    private function pushToDms(Model $model): void
    {
        $handler = SyncRegistry::handler($model);
        if (! $handler || ! method_exists($handler, 'sync')) {
            return;
        }
        try {
            $handler->sync($model);
        } catch (\Throwable $exception) {
            $this->conflict($model::class, null, $model->name ?? $model->voucher_number ?? null, 'Books row saved. DMS copy failed: '.$exception->getMessage());
        }
    }

    private function bankAccount(Ledger $ledger, \DOMElement $node): void
    {
        $payment = null;
        foreach ($node->getElementsByTagName('PAYMENTDETAILS.LIST') as $list) {
            if ($list instanceof \DOMElement && $list->parentNode === $node) {
                $payment = $list;
                break;
            }
        }
        if (! $payment instanceof \DOMElement) {
            return;
        }

        $bankName = $this->text($payment, 'BANKNAME');
        $account = $this->text($payment, 'ACCOUNTNUMBER');
        $ifsc = $this->text($payment, 'IFSCODE');
        if ($bankName === '' && $account === '' && $ifsc === '') {
            return;
        }

        BankAccount::query()->updateOrCreate(
            ['ledger_id' => $ledger->id],
            [
                'company_id' => $ledger->company_id,
                'bank_name' => $bankName !== '' ? $bankName : $ledger->name,
                'account_number' => $account !== '' ? $account : null,
                'ifsc' => $ifsc !== '' ? $ifsc : null,
            ]
        );
    }

    private function company(): ?Company
    {
        $name = trim((string) config('tally_sync.company', ''));
        if ($name !== '') {
            $found = Company::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if ($found) {
                return $found;
            }
        }

        return Company::query()->count() === 1 ? Company::query()->first() : null;
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function parentId(string $class, Company $company, string $name): ?int
    {
        $name = trim(preg_replace('/[\x00-\x1F]/', '', $name) ?? '');
        if ($name === '' || strcasecmp($name, 'Primary') === 0) {
            return null;
        }

        return $class::query()->where('company_id', $company->id)->where('name', $name)->value('id');
    }

    private function localNewer(Model $model, string $type): bool
    {
        $link = $this->linkRow($model, $type);

        return $link && $model->updated_at && $link->updated_at && $model->updated_at->gt($link->updated_at);
    }

    private function linkRow(Model $model, string $type): ?TallyLink
    {
        return TallyLink::query()
            ->where('linkable_type', $model::class)
            ->where('linkable_id', $model->getKey())
            ->where('tally_type', $type)
            ->first();
    }

    private function voucherType(string $name): ?VoucherType
    {
        $name = strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''));

        return match (true) {
            $name === '' => null,
            str_contains($name, 'sales order'), str_contains($name, 'purchase order') => null,
            str_contains($name, 'credit note') => VoucherType::CreditNote,
            str_contains($name, 'debit note') => VoucherType::DebitNote,
            str_contains($name, 'receipt note'), str_contains($name, 'delivery note'),
            str_contains($name, 'material in'), str_contains($name, 'material out'),
            str_contains($name, 'rejection'), str_contains($name, 'stock journal'),
            str_contains($name, 'physical stock'), str_contains($name, 'job work') => VoucherType::Adjustment,
            str_contains($name, 'reversing journal'), str_contains($name, 'memorandum'),
            str_contains($name, 'payroll'), str_contains($name, 'attendance'),
            str_contains($name, 'journal') => VoucherType::Journal,
            str_contains($name, 'payment') => VoucherType::Payment,
            str_contains($name, 'receipt') => VoucherType::Receipt,
            str_contains($name, 'contra') => VoucherType::Contra,
            str_contains($name, 'transfer') => VoucherType::Transfer,
            str_contains($name, 'sales') => VoucherType::Sales,
            str_contains($name, 'purchase') => VoucherType::Purchase,
            default => null,
        };
    }

    private function voucherTypeMaster(Company $company, \DOMElement $node, string $name): ?VoucherTypeMaster
    {
        $category = $this->voucherType($this->text($node, 'PARENT') ?: $name);
        if (! $category) {
            $this->counts['skipped']++;

            return null;
        }

        $taken = VoucherTypeMaster::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($name, $category) {
                $query->where('name', $name)->orWhere('category', $category->value);
            })
            ->exists();
        if ($taken) {
            $this->counts['skipped']++;

            return null;
        }

        $abbreviation = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'VCH', 0, 8));
        $suffix = 1;
        while (VoucherTypeMaster::query()->where('company_id', $company->id)->where('abbreviation', $abbreviation)->exists()) {
            $abbreviation = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'VCH', 0, 6)).$suffix;
            $suffix++;
        }

        $this->counts['created']++;

        return VoucherTypeMaster::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'abbreviation' => substr($abbreviation, 0, 12),
            'category' => $category->value,
            'numbering_method' => 'automatic',
            'prefix' => $category->prefix(),
            'suffix' => '',
            'next_number' => 1,
            'padding' => 6,
            'restart' => 'yearly',
        ]);
    }

    /**
     * @return array{created:int, updated:int, conflicts:int, skipped:int, alter_id:int}
     */
    private function report(string $key, string $xml): array
    {
        [$title, $span] = TallyExportBuilder::REPORTS[$key];
        $period = $span === 'month'
            ? now()->startOfMonth()->format('d M Y').' - '.now()->endOfMonth()->format('d M Y')
            : 'open financial year';

        $lines = [];
        $message = null;
        $status = 'ok';
        $dom = new \DOMDocument;
        if (! @$dom->loadXML($xml)) {
            $status = 'failed';
            $message = 'Tally did not return this report.';
        } elseif (preg_match('/<LINEERROR>(.*?)<\/LINEERROR>/s', $xml, $error)) {
            $status = 'failed';
            $message = trim(html_entity_decode(strip_tags($error[1])));
        } else {
            foreach (['DSPDISPNAME', 'LEDGERNAME', 'VOUCHERTYPENAME', 'VOUCHERNUMBER', 'DSPACCNAME'] as $tag) {
                foreach ($dom->getElementsByTagName($tag) as $node) {
                    if (! $node instanceof \DOMElement || $node->getElementsByTagName('*')->length > 0) {
                        continue;
                    }
                    $label = trim(preg_replace('/\s+/', ' ', $node->textContent) ?? '');
                    if ($label !== '' && strlen($label) < 180) {
                        $lines[$label] = $label;
                    }
                    if (count($lines) >= 40) {
                        break 2;
                    }
                }
            }
            if ($lines === []) {
                $status = 'empty';
                $message = 'Tally returned this report with no lines for the period.';
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('tally_report_snapshots')) {
            DB::table('tally_report_snapshots')->updateOrInsert(
                ['report' => $key],
                [
                    'title' => $title,
                    'period' => substr($period, 0, 40),
                    'status' => $status,
                    'rows' => count($lines),
                    'summary' => $lines === [] ? null : json_encode(array_values($lines)),
                    'message' => $message ? substr($message, 0, 500) : null,
                    'updated_at' => now(),
                ]
            );
        }

        $this->counts[$status === 'ok' ? 'updated' : 'skipped']++;

        return $this->counts + ['alter_id' => 0];
    }

    private function tallyType(string $key): string
    {
        return match ($key) {
            'groups' => 'books_group',
            'stock_groups' => 'books_stock_group',
            'units' => 'books_unit',
            'godowns' => 'books_godown',
            'cost_categories' => 'books_cost_category',
            'cost_centres' => 'books_cost_centre',
            'ledgers', 'bank_accounts' => 'books_ledger',
            'voucher_types' => 'books_voucher_type',
            'stock_items' => 'books_stock_item',
            'vouchers' => 'books_voucher',
            default => $key,
        };
    }

    private function text(\DOMElement $node, string $tag): string
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && strcasecmp($child->nodeName, $tag) === 0) {
                return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $child->textContent) ?? '');
            }
        }

        return '';
    }

    private function yes(string $value): bool
    {
        return in_array(strtolower($value), ['yes', 'true', '1'], true);
    }

    private function date(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) < 8) {
            return null;
        }

        return substr($digits, 0, 4).'-'.substr($digits, 4, 2).'-'.substr($digits, 6, 2);
    }

    private function leadingNumber(string $value): float
    {
        if (preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', $value), $match)) {
            return (float) $match[0];
        }

        return 0.0;
    }

    private function conflict(string $collection, ?string $remote, ?string $name, string $reason): void
    {
        $this->counts['conflicts']++;
        DB::table('tally_sync_conflicts')->insert([
            'collection' => substr($collection, 0, 40),
            'remote_id' => $remote ? substr($remote, 0, 120) : null,
            'name' => $name ? substr($name, 0, 200) : null,
            'reason' => substr($reason, 0, 2000),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{created:int, updated:int, conflicts:int, skipped:int, alter_id:int}
     */
    private function finish(string $key): array
    {
        if ($key === 'vouchers' && $this->cursorStart < TallyExportBuilder::VOUCHER_SCAN_UNTIL) {
            $this->maxAlter = max($this->maxAlter, $this->cursorStart + TallyExportBuilder::VOUCHER_SPAN);
        }

        DB::table('tally_sync_cursors')->updateOrInsert(
            ['collection' => $key],
            ['alter_id' => $this->maxAlter, 'updated_at' => now()]
        );

        return $this->counts + ['alter_id' => $this->maxAlter];
    }
}