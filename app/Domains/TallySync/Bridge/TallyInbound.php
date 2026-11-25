<?php

namespace App\Domains\TallySync\Bridge;

use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncRegistry;
use App\Domains\Tally\Observers\TallyAutoEnqueueObserver;
use App\Domains\TallySync\Import\TallyImportLookupCache;
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
use Tally\Models\Bill;
use Tally\Models\BillAllocation;
use Tally\Models\Company;
use Tally\Models\CostCategory;
use Tally\Models\CostCentre;
use Tally\Models\FinancialYear;
use Tally\Models\Godown;
use Tally\Models\GoodsReceipt;
use Tally\Models\GoodsReceiptLine;
use Tally\Models\HsnSac;
use Tally\Models\Ledger;
use Tally\Models\Party;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Inventory\StockTransactionService;
use Tally\Inventory\StockTransactionType;
use Tally\Models\StockGodownOpening;
use Tally\Models\StockTransaction;
use Tally\Models\StockTransactionLine;
use Tally\Models\TallyLink;
use Tally\Models\TaxCategory;
use Tally\Models\TaxRate;
use Tally\Models\Unit;
use Tally\Models\Voucher;
use Tally\Models\VoucherTypeMaster;
use Tally\Models\VoucherInventoryLine;
use Tally\Tax\GstRegistrationType;
use Tally\Tax\HsnKind;

class TallyInbound
{
    /** @var array<string, int> */
    private array $counts = ['created' => 0, 'updated' => 0, 'conflicts' => 0, 'skipped' => 0];

    private int $maxAlter = 0;

    private int $cursorStart = 0;

    /** When true (artisan --force), Tally wins over localNewer master conflicts. */
    private bool $forceOverwrite = false;

    private ?TallyImportLookupCache $lookupCache = null;

    /** Bulk `dms:import-tally` — skip row locks and per-item activity logs. */
    private bool $bulkXmlImport = false;

    public function setForceOverwrite(bool $force): self
    {
        $this->forceOverwrite = $force;

        return $this;
    }

    public function setLookupCache(?TallyImportLookupCache $cache): self
    {
        $this->lookupCache = $cache;

        return $this;
    }

    public function setBulkXmlImport(bool $bulk): self
    {
        $this->bulkXmlImport = $bulk;

        return $this;
    }

    /**
     * @param  'dms'|'books'  $target
     * @return array{created:int, updated:int, conflicts:int, skipped:int, alter_id:int}
     */
    public function apply(string $key, string $xml, string $target = 'dms'): array
    {
        // Defend against Title Case middleware rewriting connector keys (Vouchers → vouchers).
        $key = strtolower(str_replace(' ', '_', trim($key)));
        $target = strtolower(trim($target)) === 'books' ? 'books' : 'dms';
        $this->counts = ['created' => 0, 'updated' => 0, 'conflicts' => 0, 'skipped' => 0];
        $this->cursorStart = (int) DB::table('tally_sync_cursors')->where('collection', $key)->value('alter_id');
        $this->maxAlter = $this->cursorStart;
        if (isset(TallyExportBuilder::REPORTS[$key])) {
            return $this->report($key, $xml);
        }

        // Books-only masters are not imported into DMS. Cursor still advances so polls stay incremental.
        // DMS keys mirror outbound masters + vouchers for full connector bidirectional sync.
        $booksOnly = ['groups', 'cost_categories', 'voucher_types', 'bank_accounts', 'currencies'];
        $dmsKeys = ['stock_groups', 'units', 'godowns', 'ledgers', 'stock_items', 'cost_centres', 'vouchers', 'pay_heads'];
        $booksKeys = ['groups', 'stock_groups', 'units', 'godowns', 'cost_categories', 'cost_centres', 'ledgers', 'bank_accounts', 'voucher_types', 'stock_items', 'vouchers', 'currencies', 'pay_heads'];

        $xml = self::sanitizeTallyXml($xml);

        $tag = match ($key) {
            'groups' => 'GROUP',
            'stock_groups' => 'STOCKGROUP',
            'units' => 'UNIT',
            'godowns' => 'GODOWN',
            'cost_categories' => 'COSTCATEGORY',
            'cost_centres' => 'COSTCENTRE',
            'ledgers', 'bank_accounts', 'pay_heads' => 'LEDGER',
            'voucher_types' => 'VOUCHERTYPE',
            'stock_items' => 'STOCKITEM',
            'vouchers' => 'VOUCHER',
            'currencies' => 'CURRENCY',
            default => '',
        };

        if ($tag === '') {
            return $this->finish($key);
        }

        $organization = $this->organizationCompany();
        $booksCompany = $target === 'books' ? $this->company() : null;

        if ($target === 'dms' && in_array($key, $dmsKeys, true) && ! $organization) {
            $this->conflict($key, null, null, 'Create a DMS company first. Tally imports land in DMS, then Books syncs from DMS.');

            return $this->finish($key);
        }

        if ($target === 'books' && ! $booksCompany) {
            $this->conflict($key, null, null, 'Create / provision a Books company first before importing Tally XML into acct_*.');

            return $this->finish($key);
        }

        $dom = new \DOMDocument;
        $loaded = @$dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        if (! $loaded) {
            // Truncated / malformed exports (common on large Tally ZIP dumps): import complete entity blocks.
            return $this->applyChunked($key, $tag, $xml, $target, $organization, $booksCompany, $booksOnly, $dmsKeys, $booksKeys);
        }

        foreach ($dom->getElementsByTagName($tag) as $node) {
            if (! $node instanceof \DOMElement || $node->parentNode?->nodeName === $tag || $node->parentNode?->nodeName === 'CMPINFO') {
                continue;
            }
            $this->importNode($key, $node, $target, $organization, $booksCompany, $booksOnly, $dmsKeys, $booksKeys);
        }

        return $this->finish($key);
    }

    /**
     * Import complete top-level entity XML blocks when the full document cannot be parsed.
     *
     * @param  list<string>  $booksOnly
     * @param  list<string>  $dmsKeys
     * @param  list<string>  $booksKeys
     * @return array{created:int, updated:int, conflicts:int, skipped:int, alter_id:int}
     */
    private function applyChunked(
        string $key,
        string $tag,
        string $xml,
        string $target,
        ?\App\Domains\Organization\Models\Company $organization,
        ?Company $booksCompany,
        array $booksOnly,
        array $dmsKeys,
        array $booksKeys,
    ): array {
        $pattern = '/<'.preg_quote($tag, '/').'\b[^>]*>.*?<\/'.preg_quote($tag, '/').'>/is';
        $matched = preg_match_all($pattern, $xml, $matches);
        if (! $matched) {
            $this->conflict($key, null, null, 'Tally XML parse failed and no complete <'.$tag.'> blocks could be recovered.');

            return $this->finish($key);
        }

        // Recovered partial XML — import those blocks without counting a hard conflict.

        foreach ($matches[0] as $block) {
            $wrapper = '<?xml version="1.0" encoding="UTF-8"?><ROOT>'.$block.'</ROOT>';
            $piece = new \DOMDocument;
            if (! @$piece->loadXML($wrapper, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
                $this->counts['conflicts']++;

                continue;
            }
            $node = $piece->getElementsByTagName($tag)->item(0);
            if (! $node instanceof \DOMElement) {
                continue;
            }
            $this->importNode($key, $node, $target, $organization, $booksCompany, $booksOnly, $dmsKeys, $booksKeys);
        }

        return $this->finish($key);
    }

    /**
     * @param  list<string>  $booksOnly
     * @param  list<string>  $dmsKeys
     * @param  list<string>  $booksKeys
     */
    private function importNode(
        string $key,
        \DOMElement $node,
        string $target,
        ?\App\Domains\Organization\Models\Company $organization,
        ?Company $booksCompany,
        array $booksOnly,
        array $dmsKeys,
        array $booksKeys,
    ): void {
        $alter = (int) $this->text($node, 'ALTERID');
        // Voucher cursors are calendar Ymd values — never advance them from Tally AlterID.
        if ($key !== 'vouchers') {
            $this->maxAlter = max($this->maxAlter, $alter);
        }

        if ($target === 'dms') {
            if (in_array($key, $booksOnly, true) || ! in_array($key, $dmsKeys, true)) {
                $this->counts['skipped']++;

                return;
            }

            try {
                TallyAutoEnqueueObserver::withoutSync(function () use ($key, $node, $organization) {
                    $this->oneDms($key, $node, $organization);
                });
            } catch (\Throwable $exception) {
                $this->conflict($key, $this->text($node, 'GUID'), $this->text($node, 'NAME') ?: $node->getAttribute('NAME'), $exception->getMessage());
            }

            return;
        }

        if (! in_array($key, $booksKeys, true)) {
            $this->counts['skipped']++;

            return;
        }

        try {
            TallyAutoEnqueueObserver::withoutSync(function () use ($key, $node, $booksCompany) {
                $this->one($key, $node, $booksCompany);
            });
        } catch (\Throwable $exception) {
            $this->conflict($key, $this->text($node, 'GUID'), $this->text($node, 'NAME') ?: $node->getAttribute('NAME'), $exception->getMessage());
        }
    }

    private function oneDms(string $key, \DOMElement $node, \App\Domains\Organization\Models\Company $organization): void
    {
        if ($key === 'vouchers') {
            (new DmsVoucherImporter($organization, $this->counts))->import($node);

            return;
        }

        $name = preg_replace('/\s+/u', ' ', trim($this->text($node, 'NAME') ?: $node->getAttribute('NAME'))) ?? '';
        if ($name === '') {
            $this->counts['skipped']++;

            return;
        }

        match ($key) {
            'stock_groups' => $this->dmsStockGroup($organization, $node, $name),
            'units' => $this->dmsUnit($node, $name),
            'godowns' => $this->dmsWarehouse($organization, $node, $name),
            'ledgers' => $this->dmsCustomer($organization, $node, $name),
            'stock_items' => $this->dmsProduct($organization, $node, $name),
            'cost_centres' => $this->dmsBranch($organization, $node, $name),
            'pay_heads' => $this->dmsPayHead($organization, $node, $name),
            default => $this->counts['skipped']++,
        };
    }

    private function dmsStockGroup(\App\Domains\Organization\Models\Company $organization, \DOMElement $node, string $name): void
    {
        $parent = $this->normalizeParentName($this->text($node, 'PARENT'));

        if ($parent === '' || strcasecmp($parent, 'Primary') === 0) {
            // Prefer ProductType when a matching type already exists; else Brand.
            $productType = \App\Domains\Master\Models\ProductType::query()
                ->where('company_id', $organization->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();
            if ($productType) {
                $productType->fill(['name' => $name, 'is_active' => true])->save();
                $this->counts['updated']++;
                $this->mapDms('stock_group', \App\Domains\Master\Models\ProductType::class, $name, $this->text($node, 'GUID') ?: null);

                return;
            }

            $brand = \App\Domains\Catalog\Models\Brand::query()
                ->where('company_id', $organization->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            $attributes = [
                'company_id' => $organization->id,
                'name' => $name,
                'is_active' => true,
            ];

            if ($brand) {
                $brand->fill($attributes)->save();
                $this->counts['updated']++;
            } else {
                \App\Domains\Catalog\Models\Brand::query()->create($attributes);
                $this->counts['created']++;
            }

            $this->mapDms('stock_group', \App\Domains\Catalog\Models\Brand::class, $name, $this->text($node, 'GUID') ?: null);

            return;
        }

        // Parent is a category → this node is a SubCategory.
        $parentCategory = \App\Domains\Catalog\Models\Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($parent)])
            ->whereHas('brand', fn ($q) => $q->where('company_id', $organization->id))
            ->first();
        if ($parentCategory) {
            $sub = \App\Domains\Catalog\Models\SubCategory::query()
                ->where('category_id', $parentCategory->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();
            $attributes = [
                'category_id' => $parentCategory->id,
                'name' => $name,
                'is_active' => true,
            ];
            if ($sub) {
                $sub->fill($attributes)->save();
                $this->counts['updated']++;
            } else {
                \App\Domains\Catalog\Models\SubCategory::query()->create($attributes);
                $this->counts['created']++;
            }
            $this->mapDms('stock_group', \App\Domains\Catalog\Models\SubCategory::class, $name, $this->text($node, 'GUID') ?: null);

            return;
        }

        $brand = \App\Domains\Catalog\Models\Brand::query()
            ->where('company_id', $organization->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($parent)])
            ->first();

        if (! $brand) {
            $brand = \App\Domains\Catalog\Models\Brand::query()->create([
                'company_id' => $organization->id,
                'name' => $parent,
                'is_active' => true,
            ]);
            $this->counts['created']++;
        }

        $category = \App\Domains\Catalog\Models\Category::query()
            ->where('brand_id', $brand->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        $attributes = [
            'brand_id' => $brand->id,
            'name' => $name,
            'is_active' => true,
        ];

        if ($category) {
            $category->fill($attributes)->save();
            $this->counts['updated']++;
        } else {
            try {
                \App\Domains\Catalog\Models\Category::query()->create($attributes);
                $this->counts['created']++;
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $category = \App\Domains\Catalog\Models\Category::query()
                    ->where('brand_id', $brand->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first();
                if ($category) {
                    $category->fill($attributes)->save();
                    $this->counts['updated']++;
                }
            }
        }

        $this->mapDms('stock_group', \App\Domains\Catalog\Models\Category::class, $name, $this->text($node, 'GUID') ?: null);
    }

    private function dmsBranch(\App\Domains\Organization\Models\Company $organization, \DOMElement $node, string $name): void
    {
        $branch = \App\Domains\Organization\Models\Branch::query()
            ->where('company_id', $organization->id)
            ->where(function ($q) use ($name) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($name)]);
            })
            ->first();

        $address = $this->addressLinesFromLedger($node);
        $lines = preg_split("/\r\n|\n|\r/", $address) ?: [];
        $attributes = [
            'company_id' => $organization->id,
            'name' => $name,
            'address' => $address !== '' ? ($lines[0] ?? $address) : null,
            'address_line_2' => count($lines) > 1 ? trim(implode(', ', array_slice($lines, 1))) : null,
            'state' => $this->truncateField($this->stateFromLedger($node) ?: null, 80),
            'pincode' => $this->truncateField($this->pincodeFromLedger($node) ?: null, 12),
            'country' => $this->truncateField($this->countryFromLedger($node) ?: null, 80) ?: 'India',
            'phone' => $this->truncateField($this->phoneFromLedger($node), 30),
            'email' => $this->truncateField($this->emailFromLedger($node), 120),
            'is_active' => true,
        ];

        if ($branch) {
            $branch->fill($this->withoutBlank($attributes))->save();
            $this->counts['updated']++;
        } else {
            \App\Domains\Organization\Models\Branch::query()->create($attributes + [
                'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'BR', 0, 12)),
            ]);
            $this->counts['created']++;
        }

        $this->mapDms('cost_centre', \App\Domains\Organization\Models\Branch::class, $name, $this->text($node, 'GUID') ?: null);
    }

    private function normalizeParentName(?string $parent): string
    {
        $parent = trim(str_replace("\x04", '', (string) $parent));

        return preg_replace('/\s+/u', ' ', trim($parent)) ?? '';
    }

    private function dmsGstRate(\DOMElement $node): ?float
    {
        $igst = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        foreach ($node->getElementsByTagName('RATEDETAILS.LIST') as $rateNode) {
            if (! $rateNode instanceof \DOMElement) {
                continue;
            }
            $head = strtoupper($this->text($rateNode, 'GSTRATEDUTYHEAD'));
            $rate = $this->leadingNumber($this->text($rateNode, 'GSTRATE'));
            if (str_contains($head, 'IGST')) {
                $igst = $rate;
            } elseif (str_contains($head, 'CGST')) {
                $cgst = $rate;
            } elseif (str_contains($head, 'SGST') || str_contains($head, 'UTGST')) {
                $sgst = $rate;
            }
        }
        $total = $igst > 0 ? $igst : ($cgst + $sgst);

        return $total > 0 ? round($total, 2) : null;
    }

    private function dmsUnit(\DOMElement $node, string $name): void
    {
        $original = $this->text($node, 'ORIGINALNAME') ?: $name;
        $uom = \App\Domains\Master\Models\Uom::query()
            ->where('code', $name)
            ->orWhere('name', $original)
            ->orWhere('name', $name)
            ->first();

        $attributes = [
            'name' => $original,
            'code' => $name,
            'is_active' => true,
        ];

        if ($uom) {
            $uom->fill($attributes)->save();
            $this->counts['updated']++;
        } else {
            \App\Domains\Master\Models\Uom::query()->create($attributes);
            $this->counts['created']++;
        }

        $this->mapDms('uom', \App\Domains\Master\Models\Uom::class, $name, $this->text($node, 'GUID') ?: null);
    }

    private function dmsWarehouse(\App\Domains\Organization\Models\Company $organization, \DOMElement $node, string $name): void
    {
        $branchId = \App\Domains\Organization\Models\Branch::query()
            ->where('company_id', $organization->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if (! $branchId) {
            $branch = \App\Domains\Organization\Models\Branch::query()->create([
                'company_id' => $organization->id,
                'name' => 'Main',
                'code' => 'MAIN',
                'is_active' => true,
            ]);
            $branchId = $branch->id;
            $this->counts['created']++;
        }

        $warehouse = \App\Domains\Organization\Models\Warehouse::query()
            ->where('company_id', $organization->id)
            ->where(function ($query) use ($name) {
                $query->where('name', $name)->orWhere('code', $name);
            })
            ->first();

        $address = $this->addressLinesFromLedger($node);
        $lines = preg_split("/\r\n|\n|\r/", $address) ?: [];
        $attributes = [
            'company_id' => $organization->id,
            'branch_id' => $branchId,
            'name' => $name,
            'address' => $address !== '' ? ($lines[0] ?? $address) : null,
            'address_line_2' => count($lines) > 1 ? trim(implode(', ', array_slice($lines, 1))) : null,
            'state' => $this->truncateField($this->stateFromLedger($node) ?: null, 80),
            'pincode' => $this->truncateField($this->pincodeFromLedger($node) ?: null, 12),
            'country' => $this->truncateField($this->countryFromLedger($node) ?: null, 80) ?: 'India',
            'contact_person' => $this->truncateField($this->contactPersonFromLedger($node) ?: null, 120),
            'contact_phone' => $this->truncateField($this->phoneFromLedger($node), 30),
            'is_active' => true,
        ];

        if ($warehouse) {
            $warehouse->fill($this->withoutBlank($attributes))->save();
            $this->counts['updated']++;
        } else {
            \App\Domains\Organization\Models\Warehouse::query()->create($attributes + [
                'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'WH', 0, 12)),
            ]);
            $this->counts['created']++;
        }

        $this->mapDms('godown', \App\Domains\Organization\Models\Warehouse::class, $name, $this->text($node, 'GUID') ?: null);
    }

    private function dmsCustomer(\App\Domains\Organization\Models\Company $organization, \DOMElement $node, string $name): void
    {
        $parent = strtolower($this->text($node, 'PARENT'));
        $role = $this->partyRoleFromParent($parent);
        if ($role === null) {
            // Non-party ledgers (cash/bank/expense) belong in Books only — quiet on DMS path.
            return;
        }

        $customer = \App\Domains\Master\Models\Customer::query()
            ->where('company_id', $organization->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        $gstin = $this->gstinFromLedger($node);
        $pan = $this->panFromLedger($node, $gstin);
        $address = $this->addressLinesFromLedger($node);
        $state = $this->stateFromLedger($node);
        $pincode = $this->pincodeFromLedger($node);
        $phone = $this->phoneFromLedger($node);
        $email = $this->emailFromLedger($node);
        $tradeName = $this->mailingNameFromLedger($node, $name);
        $gstReg = \App\Domains\Master\Models\Customer::normalizeGstRegistrationType(
            $this->gstRegistrationTypeFromLedger($node)
        );
        $creditLimitRaw = $this->text($node, 'CREDITLIMIT');
        $creditDaysRaw = $this->text($node, 'BILLCREDITPERIOD');
        $interestOn = $this->yes($this->text($node, 'ISINTERESTON'));
        $interestRateRaw = $this->text($node, 'INTERESTRATE');
        $website = $this->firstNonEmptyText($node, ['WEBSITE', 'LEDGERWEBSITE']);
        $openedOn = $this->date($this->firstNonEmptyText($node, ['CREATEDDATE', 'STARTINGFROM', 'OPENINGDATE']));
        $udyam = $this->udyamFromLedger($node);

        $attributes = [
            'company_id' => $organization->id,
            'name' => $name,
            'party_type' => $role === 'supplier'
                ? \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS
                : \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'trade_name' => $this->truncateField($tradeName !== '' ? $tradeName : null, 180),
            'phone' => $this->truncateField($phone, 30),
            'email' => $this->truncateField($email, 120),
            'website' => $this->truncateField($website !== '' ? $website : null, 255),
            'opened_on' => $openedOn,
            'udyam_number' => $this->truncateField($udyam !== '' ? $udyam : null, 50),
            'address' => $address !== '' ? $address : null,
            'gstin' => $this->truncateField($gstin !== '' ? $gstin : null, 20),
            'pan' => $this->truncateField($pan !== '' ? $pan : null, 20),
            'gst_registration_type' => $gstReg,
            'state' => $this->truncateField($state !== '' ? $state : null, 80),
            'pincode' => $this->truncateField($pincode !== '' ? $pincode : null, 12),
            'is_active' => true,
        ];

        if (trim($creditLimitRaw) !== '') {
            $attributes['credit_limit'] = (float) str_replace(',', '', $creditLimitRaw);
        }
        if (trim($creditDaysRaw) !== '') {
            $attributes['credit_days'] = (int) $creditDaysRaw;
        }
        if ($interestOn && trim($interestRateRaw) !== '') {
            $attributes['interest_rate'] = (float) str_replace(',', '', $interestRateRaw);
        }

        if ($customer) {
            $customer->fill($this->withoutBlank($attributes))->save();
            $this->counts['updated']++;
        } else {
            $customer = \App\Domains\Master\Models\Customer::query()->create($attributes + [
                'code' => \App\Support\CodeGenerator::forParty($organization->id),
                'credit_limit' => $attributes['credit_limit'] ?? 0,
                'credit_days' => $attributes['credit_days'] ?? 0,
            ]);
            $this->counts['created']++;
        }

        $this->syncPartyAddressFromLedger($customer, $node);
        $this->syncPartyBankFromLedger($customer, $node);
        $this->syncPartyDirectoryContact($customer, $node);
        $this->maybeUpdateCompanyBank($organization, $node);

        $this->mapDms('ledger', \App\Domains\Master\Models\Customer::class, $name, $this->text($node, 'GUID') ?: null);
    }

    private function syncPartyAddressFromLedger(\App\Domains\Master\Models\Customer $customer, \DOMElement $node): void
    {
        $address = $this->addressLinesFromLedger($node);
        $state = $this->stateFromLedger($node);
        $pincode = $this->pincodeFromLedger($node);
        $gstin = $this->gstinFromLedger($node);
        $contact = $this->contactPersonFromLedger($node);
        $contactPhone = $this->phoneFromLedger($node);
        if ($address === '' && $state === '' && $pincode === '' && $gstin === '' && $contact === '' && ! $contactPhone) {
            return;
        }

        $existing = $customer->addresses()->where('is_default_billing', true)->first()
            ?: $customer->addresses()->orderBy('id')->first();

        $lines = preg_split("/\r\n|\n|\r/", $address) ?: [];
        $line1 = trim((string) ($lines[0] ?? ''));
        $line2 = trim(implode(', ', array_values(array_filter(array_slice($lines, 1)))));

        $attrs = [
            'customer_id' => $customer->id,
            'type' => 'billing',
            'label' => 'Tally',
            'address' => $address !== '' ? $address : null,
            'address_line_1' => $line1 !== '' ? $line1 : null,
            'address_line_2' => $line2 !== '' ? $line2 : null,
            'state' => $state !== '' ? $state : null,
            'pincode' => $pincode !== '' ? $pincode : null,
            'gstin' => $gstin !== '' ? $gstin : null,
            'contact_person' => $contact !== '' ? $this->truncateField($contact, 120) : null,
            'contact_phone' => $this->truncateField($contactPhone, 30),
            'is_default' => true,
            'is_default_billing' => true,
            'is_active' => true,
        ];

        if ($existing) {
            $existing->fill($this->withoutBlank($attrs))->save();
        } else {
            \App\Domains\Master\Models\PartyAddress::query()->create($attrs);
            $this->counts['created']++;
        }
    }

    private function syncPartyBankFromLedger(\App\Domains\Master\Models\Customer $customer, \DOMElement $node): void
    {
        $payment = $this->preferredPaymentDetails($node);
        if (! $payment instanceof \DOMElement) {
            return;
        }

        $bankName = $this->text($payment, 'BANKNAME') ?: $this->firstNestedText($payment, 'BANKNAME');
        $account = $this->text($payment, 'ACCOUNTNUMBER') ?: $this->firstNestedText($payment, 'ACCOUNTNUMBER');
        $ifsc = $this->text($payment, 'IFSCODE') ?: $this->firstNestedText($payment, 'IFSCODE');
        if ($bankName === '' && $account === '' && $ifsc === '') {
            return;
        }

        $existing = $customer->bankAccounts()->where('is_primary', true)->first()
            ?: $customer->bankAccounts()->orderBy('id')->first();

        $branch = $this->text($payment, 'BRANCHNAME')
            ?: $this->text($payment, 'BRANCH')
            ?: $this->firstNestedText($payment, 'BRANCHNAME')
            ?: $this->firstNestedText($payment, 'BRANCH');
        $attrs = [
            'customer_id' => $customer->id,
            'bank_name' => $this->truncateField($bankName !== '' ? $bankName : 'Bank', 120) ?: 'Bank',
            'account_number' => $this->truncateField($account !== '' ? $account : null, 64),
            'ifsc_code' => $this->truncateField($ifsc !== '' ? strtoupper(preg_replace('/\s+/', '', $ifsc) ?? $ifsc) : null, 20),
            'branch_name' => $this->truncateField($branch !== '' ? $branch : null, 120),
            'account_holder_name' => $this->truncateField($customer->name, 120),
            'is_primary' => true,
            'is_active' => true,
        ];

        try {
            if ($existing) {
                $existing->fill($this->withoutBlank($attrs))->save();
            } else {
                \App\Domains\Master\Models\PartyBankAccount::query()->create($attrs);
                $this->counts['created']++;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function syncPartyDirectoryContact(\App\Domains\Master\Models\Customer $customer, \DOMElement $node): void
    {
        $contactName = $this->contactPersonFromLedger($node);
        $phone = $this->phoneFromLedger($node);
        $email = $this->emailFromLedger($node);
        if ($contactName === '' && ! $phone && ! $email) {
            return;
        }

        $displayName = $contactName !== '' ? $contactName : ($customer->name.' Contact');
        $query = \App\Domains\PhoneDirectory\Models\PhoneDirectoryContact::query()
            ->where('company_id', $customer->company_id);
        if ($phone) {
            $query->where(function ($q) use ($phone, $displayName) {
                $q->where('mobile_number', $phone)
                    ->orWhereRaw('LOWER(contact_name) = ?', [mb_strtolower($displayName)]);
            });
        } else {
            $query->whereRaw('LOWER(contact_name) = ?', [mb_strtolower($displayName)]);
        }
        $contact = $query->first();

        $type = $customer->party_type === \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS
            ? 'supplier'
            : 'customer';

        $attrs = [
            'company_id' => $customer->company_id,
            'contact_name' => $displayName,
            'contact_type' => $type,
            'company_name' => $customer->name,
            'mobile_number' => $this->truncateField($phone, 20),
            'email' => $this->truncateField($email, 120),
            'state' => $this->truncateField($customer->state, 80),
            'address' => $customer->address,
            'pincode' => $this->truncateField($customer->pincode, 12),
            'status' => 'active',
        ];

        try {
            if ($contact) {
                $contact->fill($this->withoutBlank($attrs))->save();
            } else {
                $contact = \App\Domains\PhoneDirectory\Models\PhoneDirectoryContact::query()->create($attrs);
                $this->counts['created']++;
            }

            $partyContact = \App\Domains\Master\Models\PartyContact::query()
                ->where('customer_id', $customer->id)
                ->where(function ($q) use ($contact, $phone, $displayName) {
                    $q->where('phone_directory_contact_id', $contact->id);
                    if ($phone) {
                        $q->orWhere('phone', $phone);
                    }
                    $q->orWhereRaw('LOWER(name) = ?', [mb_strtolower($displayName)]);
                })
                ->first();

            $partyAttrs = [
                'customer_id' => $customer->id,
                'phone_directory_contact_id' => $contact->id,
                'name' => $displayName,
                'phone' => $this->truncateField($phone, 20),
                'email' => $this->truncateField($email, 120),
                'role' => 'Contact',
                'is_primary' => true,
                'is_active' => true,
            ];

            if ($partyContact) {
                $partyContact->fill($this->withoutBlank($partyAttrs))->save();
            } else {
                \App\Domains\Master\Models\PartyContact::query()->create($partyAttrs);
                $this->counts['created']++;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function maybeUpdateCompanyBank(\App\Domains\Organization\Models\Company $organization, \DOMElement $node): void
    {
        // Prefer explicit company bank string from payment details ("HDFC BANK 50200088458819").
        $companyBank = $this->firstNonEmptyText($node, ['COMPANYBANK']);
        $bankName = '';
        $account = '';
        $ifsc = '';
        $branch = '';

        if ($companyBank !== '') {
            if (preg_match('/^(.*?)[\s\-]+(\d{9,20})\s*$/u', $companyBank, $match)) {
                $bankName = trim($match[1]);
                $account = trim($match[2]);
            } else {
                $bankName = $companyBank;
            }
        }

        $parent = strtolower($this->text($node, 'PARENT'));
        $isBankLedger = str_contains($parent, 'bank');
        $payment = $this->preferredPaymentDetails($node);
        if ($payment instanceof \DOMElement && ($isBankLedger || $companyBank === '')) {
            // Avoid mixing COMPANYBANK (e.g. HDFC+account) with an unrelated party IFSC.
            $bankName = $bankName !== '' ? $bankName : ($this->text($payment, 'BANKNAME') ?: $this->firstNestedText($payment, 'BANKNAME'));
            $account = $account !== '' ? $account : ($this->text($payment, 'ACCOUNTNUMBER') ?: $this->firstNestedText($payment, 'ACCOUNTNUMBER'));
            $ifsc = $this->text($payment, 'IFSCODE') ?: $this->firstNestedText($payment, 'IFSCODE');
            $branch = $this->text($payment, 'BRANCHNAME')
                ?: $this->text($payment, 'BRANCH')
                ?: $this->firstNestedText($payment, 'BRANCHNAME')
                ?: $this->firstNestedText($payment, 'BRANCH');
        }

        // Only stamp company profile from clear bank-account ledgers or COMPANYBANK tags.
        if (! $isBankLedger && $companyBank === '') {
            return;
        }
        if ($bankName === '' && $account === '' && $ifsc === '') {
            return;
        }

        $attrs = [];
        // Prefer a coherent bank-ledger snapshot (name+account+ifsc together) over piecemeal blanks.
        $hasFullBankSnapshot = $isBankLedger && $account !== '' && ($bankName !== '' || $ifsc !== '');
        if ($bankName !== '' && (blank($organization->bank_name) || $hasFullBankSnapshot)) {
            $attrs['bank_name'] = $this->truncateField($bankName, 120);
        }
        if ($account !== '' && (blank($organization->bank_account_no) || $hasFullBankSnapshot)) {
            $attrs['bank_account_no'] = $this->truncateField($account, 64);
        }
        if ($ifsc !== '' && (blank($organization->bank_ifsc) || $hasFullBankSnapshot)) {
            $attrs['bank_ifsc'] = $this->truncateField(strtoupper(preg_replace('/\s+/', '', $ifsc) ?? $ifsc), 20);
        }
        if ($branch !== '' && (blank($organization->bank_branch) || $hasFullBankSnapshot)) {
            $attrs['bank_branch'] = $this->truncateField($branch, 120);
        }
        // COMPANYBANK with account but no IFSC: don't leave a mismatched prior IFSC.
        if ($companyBank !== '' && $account !== '' && $ifsc === '' && filled($organization->bank_ifsc) && ! $isBankLedger) {
            // keep existing ifsc; name/account still fill blanks above
        }

        if ($attrs === []) {
            return;
        }

        try {
            $organization->fill($attrs)->save();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function udyamFromLedger(\DOMElement $node): string
    {
        foreach (['MSMEREGISTRATIONNUMBER', 'UDYAMNUMBER', 'UDYAM', 'MSMENO'] as $tag) {
            $value = $this->firstNonEmptyText($node, [$tag]);
            if ($value !== '') {
                return $value;
            }
        }

        foreach ($node->getElementsByTagName('MSMEREGISTRATIONDETAILS.LIST') as $list) {
            if (! $list instanceof \DOMElement) {
                continue;
            }
            foreach (['MSMEREGISTRATIONNUMBER', 'UDYAMNUMBER', 'REGISTRATIONNUMBER', 'NUMBER'] as $tag) {
                $value = $this->cleanXmlText($this->text($list, $tag) ?: $this->firstNestedText($list, $tag));
                if ($value !== '' && ! $this->isTallyPlaceholder($value)) {
                    return $value;
                }
            }
        }

        return '';
    }

    private function dmsPayHead(\App\Domains\Organization\Models\Company $organization, \DOMElement $node, string $name): void
    {
        $parent = strtolower($this->text($node, 'PARENT'));
        // Payroll export LEDGERs are pay-head candidates; only skip clear party/cash ledgers.
        if ($this->partyRoleFromParent($parent) !== null || str_contains($parent, 'bank') || str_contains($parent, 'cash')) {
            return;
        }
        if (! $this->looksLikePayHead($parent, $name) && ! str_contains($parent, 'indirect') && ! str_contains($parent, 'direct')) {
            return;
        }

        $type = str_contains($parent, 'deduct') || str_contains(strtolower($name), 'deduct') || str_contains(strtolower($name), 'pf') || str_contains(strtolower($name), 'esi')
            ? \App\Domains\Hrms\Models\PayHead::TYPE_DEDUCTION
            : \App\Domains\Hrms\Models\PayHead::TYPE_EARNING;

        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'PH', 0, 20));
        $payHead = \App\Domains\Hrms\Models\PayHead::query()
            ->where('company_id', $organization->id)
            ->where(function ($q) use ($name, $code) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhere('code', $code);
            })
            ->first();

        $attrs = [
            'company_id' => $organization->id,
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'is_system' => false,
            'is_active' => true,
        ];

        if ($payHead) {
            $payHead->fill($attrs)->save();
            $this->counts['updated']++;
        } else {
            \App\Domains\Hrms\Models\PayHead::query()->create($attrs);
            $this->counts['created']++;
        }
    }

    private function partyRoleFromParent(string $parent): ?string
    {
        $parent = strtolower(trim($parent));
        if ($parent === '') {
            return null;
        }
        if (str_contains($parent, 'creditor') || str_contains($parent, 'payable') || str_contains($parent, 'supplier')) {
            return 'supplier';
        }
        if (str_contains($parent, 'debtor') || str_contains($parent, 'receivable') || str_contains($parent, 'customer')) {
            return 'customer';
        }

        return null;
    }

    private function looksLikePayHead(string $parent, string $name): bool
    {
        $hay = strtolower($parent.' '.$name);

        return str_contains($hay, 'pay head')
            || str_contains($hay, 'payhead')
            || str_contains($hay, 'earning')
            || str_contains($hay, 'deduction')
            || str_contains($hay, 'salary')
            || str_contains($hay, 'payroll')
            || str_contains($hay, 'basic')
            || str_contains($hay, 'allowance')
            || str_contains($hay, 'provident')
            || str_contains($hay, ' employee ')
            || str_contains($hay, 'esi')
            || str_contains($hay, 'pf ')
            || str_ends_with($hay, ' pf')
            || str_contains($hay, 'gratuity')
            || str_contains($hay, 'bonus');
    }

    private function dmsProduct(\App\Domains\Organization\Models\Company $organization, \DOMElement $node, string $name): void
    {
        $unitName = $this->text($node, 'BASEUNITS') ?: 'Nos';
        $uom = $this->resolveDmsUom($unitName);

        [$brandId, $categoryId, $subCategoryId] = $this->resolveProductParents(
            $organization,
            $this->normalizeParentName($this->text($node, 'PARENT'))
        );

        $guid = $this->text($node, 'GUID') ?: null;
        $taxRate = $this->dmsGstRate($node);
        $supplyType = stripos($this->text($node, 'GSTTYPEOFSUPPLY'), 'service') !== false ? 'services' : 'goods';
        $mrp = $this->leadingNumber($this->firstNonEmptyText($node, ['MRPRATE', 'MRP', 'STANDARDPRICE']));
        $purchase = $this->leadingNumber($this->firstNonEmptyText($node, ['STANDARDCOST', 'PURCHASERATE', 'RATE']));
        $attributes = [
            'company_id' => $organization->id,
            'name' => $name,
            'description' => $this->text($node, 'DESCRIPTION') ?: null,
            'base_uom_id' => $uom->id,
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
            'hsn_code' => $this->text($node, 'HSNCODE') ?: null,
            'tax_rate' => $taxRate ?? 0,
            'is_active' => true,
        ];
        if ($mrp > 0) {
            $attributes['calculation_mrp'] = $mrp;
            $attributes['selling_price'] = $mrp;
        }
        if ($purchase > 0) {
            $attributes['purchase_price'] = $purchase;
        }

        // GUID first, then normalized name. Row locks only for live connector polls (not bulk XML).
        $persist = function () use ($organization, $guid, $name, $attributes, $supplyType) {
            $found = $this->findDmsProduct($organization, $guid, $name);
            if ($found) {
                $found->fill($this->withoutBlank($attributes))->save();
                $this->counts['updated']++;

                return $found;
            }

            $created = \App\Domains\Master\Models\Product::query()->create($attributes + [
                'sku' => \App\Support\CodeGenerator::forProduct($organization->id),
                'serial_no' => \App\Support\CodeGenerator::forProductSerial(),
                'supply_type' => $supplyType,
            ]);
            $this->counts['created']++;

            return $created;
        };

        $product = $this->bulkXmlImport
            ? $persist()
            : DB::transaction($persist);

        app(\App\Domains\Tally\Services\TallySyncMappingService::class)->createOrUpdate(
            $product,
            \App\Domains\Master\Models\Product::class,
            'stock_item',
            $guid,
            $name,
            'synced'
        );
        $this->lookupCache?->rememberDmsProduct((int) $organization->id, $name, (int) $product->id);
        $this->lookupCache?->rememberDmsProductGuid($guid, (int) $product->id);
        if ($mrp > 0) {
            $this->syncProductPriceMaster($organization, $product, $mrp, $uom->id);
        }
        if (! $this->bulkXmlImport) {
            \App\Domains\TallySync\Support\InboundActivityLog::item('stock_item', 'synced', $name);
        }
    }

    private function resolveDmsUom(string $unitName): \App\Domains\Master\Models\Uom
    {
        $cachedId = $this->lookupCache?->dmsUomId($unitName);
        if ($cachedId) {
            $uom = \App\Domains\Master\Models\Uom::query()->find($cachedId);
            if ($uom) {
                return $uom;
            }
        }

        $uom = \App\Domains\Master\Models\Uom::query()
            ->where(function ($q) use ($unitName) {
                $q->whereRaw('LOWER(code) = ?', [mb_strtolower($unitName)])
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($unitName)]);
            })
            ->first();
        if (! $uom) {
            $uom = \App\Domains\Master\Models\Uom::query()->create([
                'name' => $unitName,
                'code' => $unitName,
                'is_active' => true,
            ]);
            $this->counts['created']++;
        }

        $this->lookupCache?->rememberDmsUom($unitName, (int) $uom->id);

        return $uom;
    }

    private function syncProductPriceMaster(
        \App\Domains\Organization\Models\Company $organization,
        \App\Domains\Master\Models\Product $product,
        float $mrp,
        ?int $uomId
    ): void {
        try {
            $type = \App\Domains\Master\Models\CustomerType::query()
                ->whereRaw('LOWER(name) = ?', ['tally mrp'])
                ->first();
            if (! $type) {
                $type = \App\Domains\Master\Models\CustomerType::query()->create([
                    'name' => 'Tally MRP',
                    'code' => 'TALLY-MRP',
                    'description' => 'Imported from Tally stock item MRP rates (no PRICELEVEL in export).',
                    'is_active' => true,
                ]);
                $this->counts['created']++;
            }

            $existing = \App\Domains\Master\Models\PriceMaster::query()
                ->where('company_id', $organization->id)
                ->where('product_id', $product->id)
                ->where('customer_type_id', $type->id)
                ->where(function ($q) {
                    $q->where('type', \App\Domains\Master\Models\PriceMaster::TYPE_CUSTOMER_TYPE)
                        ->orWhereNull('type');
                })
                ->first();

            $attrs = [
                'company_id' => $organization->id,
                'type' => \App\Domains\Master\Models\PriceMaster::TYPE_CUSTOMER_TYPE,
                'name' => $product->name,
                'customer_type_id' => $type->id,
                'product_id' => $product->id,
                'uom_id' => $uomId,
                'rate' => $mrp,
                'entered_rate' => $mrp,
                'rate_includes_gst' => false,
                'retail_price' => $mrp,
                'retail_price_entered' => $mrp,
                'retail_price_gst_mode' => 'exclusive',
                'min_qty' => 1,
            ];

            if ($existing) {
                $existing->fill($this->withoutBlank($attrs))->save();
            } else {
                \App\Domains\Master\Models\PriceMaster::query()->create($attrs);
                $this->counts['created']++;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function findDmsProduct(
        \App\Domains\Organization\Models\Company $organization,
        ?string $guid,
        string $name
    ): ?\App\Domains\Master\Models\Product {
        $useLock = ! $this->bulkXmlImport;

        if ($guid) {
            $entityId = $this->lookupCache?->dmsProductIdByGuid($guid);
            if (! $entityId) {
                $mapping = app(\App\Domains\Tally\Services\TallySyncMappingService::class)->findForTally('stock_item', $guid);
                $entityId = $mapping ? (int) $mapping->entity_id : null;
            }
            if ($entityId) {
                $query = \App\Domains\Master\Models\Product::query()
                    ->whereKey($entityId)
                    ->where('company_id', $organization->id);
                if ($useLock) {
                    $query->lockForUpdate();
                }
                $byGuid = $query->first();
                if ($byGuid) {
                    return $byGuid;
                }
            }
        }

        $cachedId = $this->lookupCache?->dmsProductId((int) $organization->id, $name);
        if ($cachedId) {
            $query = \App\Domains\Master\Models\Product::query()
                ->whereKey($cachedId)
                ->where('company_id', $organization->id);
            if ($useLock) {
                $query->lockForUpdate();
            }
            $byName = $query->first();
            if ($byName) {
                return $byName;
            }
        }

        $normalized = \App\Support\TitleCase::format($name);

        $query = \App\Domains\Master\Models\Product::query()
            ->where('company_id', $organization->id)
            ->where(function ($q) use ($name, $normalized) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($normalized)]);
            })
            ->orderBy('id');
        if ($useLock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @return array{0:?int,1:?int,2:?int} brand_id, category_id, sub_category_id
     */
    private function resolveProductParents(\App\Domains\Organization\Models\Company $organization, string $parent): array
    {
        if ($parent === '' || strcasecmp($parent, 'Primary') === 0) {
            return [null, null, null];
        }

        $cached = $this->lookupCache?->dmsProductParentIds((int) $organization->id, $parent);
        if ($cached !== null) {
            return $cached;
        }

        $sub = \App\Domains\Catalog\Models\SubCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($parent)])
            ->whereHas('category.brand', fn ($q) => $q->where('company_id', $organization->id))
            ->first();
        if ($sub) {
            $ids = [$sub->category?->brand_id, $sub->category_id, $sub->id];
            $this->lookupCache?->rememberDmsProductParent((int) $organization->id, $parent, $ids);

            return $ids;
        }

        $category = \App\Domains\Catalog\Models\Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($parent)])
            ->whereHas('brand', fn ($q) => $q->where('company_id', $organization->id))
            ->first();
        if ($category) {
            $ids = [$category->brand_id, $category->id, null];
            $this->lookupCache?->rememberDmsProductParent((int) $organization->id, $parent, $ids);

            return $ids;
        }

        $brandId = $this->lookupCache?->dmsBrandId((int) $organization->id, $parent);
        $brand = null;
        if ($brandId) {
            $brand = \App\Domains\Catalog\Models\Brand::query()->find($brandId);
        }
        if (! $brand) {
            $brand = \App\Domains\Catalog\Models\Brand::query()
                ->where('company_id', $organization->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($parent)])
                ->first();
        }

        if (! $brand) {
            $brand = \App\Domains\Catalog\Models\Brand::query()->create([
                'company_id' => $organization->id,
                'name' => $parent,
                'is_active' => true,
            ]);
            $this->counts['created']++;
        }

        $this->lookupCache?->rememberDmsBrand((int) $organization->id, $parent, (int) $brand->id);
        $ids = [$brand->id, null, null];
        $this->lookupCache?->rememberDmsProductParent((int) $organization->id, $parent, $ids);

        return $ids;
    }

    private function mapDms(string $tallyType, string $dmsClass, string $name, ?string $guid): void
    {
        $id = $dmsClass::query()->where(function ($query) use ($name, $dmsClass) {
            $query->where('name', $name);
            if (\Illuminate\Support\Facades\Schema::hasColumn((new $dmsClass)->getTable(), 'code')) {
                $query->orWhere('code', $name);
            }
        })->orderByDesc('id')->value('id');

        if (! $id) {
            return;
        }

        $model = $dmsClass::query()->find($id);
        if ($model) {
            app(\App\Domains\Tally\Services\TallySyncMappingService::class)->createOrUpdate(
                $model,
                $dmsClass,
                $tallyType,
                $guid,
                $name,
                'synced'
            );
            \App\Domains\TallySync\Support\InboundActivityLog::item($tallyType, 'synced', $name);
        }
    }

    private function organizationCompany(): ?\App\Domains\Organization\Models\Company
    {
        $name = trim((string) config('tally_sync.company', ''));
        if ($name !== '') {
            $found = \App\Domains\Organization\Models\Company::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();
            if ($found) {
                return $found;
            }
        }

        return \App\Domains\Organization\Models\Company::query()
            ->where('is_default', true)
            ->orderBy('id')
            ->first()
            ?: \App\Domains\Organization\Models\Company::query()->orderBy('id')->first();
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
            'pay_heads' => $this->booksPayHead($company, $node, $name),
            'voucher_types' => $this->voucherTypeMaster($company, $node, $name),
            'stock_items' => $this->stockItem($company, $node, $name),
            'currencies' => $this->currency($company, $node, $name),
            'vouchers' => $this->voucherOrStockJournal($company, $node),
            default => null,
        };

        if ($model instanceof Model) {
            // Stock journals / receipt notes link themselves with the correct tally_type.
            if (! $model instanceof StockTransaction && ! $model instanceof GoodsReceipt) {
                $this->link($company, $model, $this->tallyType($key), $node);
            }
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

        $values = [
            'parent_id' => $this->parentId(AccountGroup::class, $company, $this->text($node, 'PARENT')),
            'nature' => $nature,
            'affects_gross_profit' => $this->yes($this->text($node, 'AFFECTSGROSSPROFIT')),
            'is_active' => true,
        ];
        $code = $this->inferStandardGroupCode($name);
        if ($code !== null) {
            $values['code'] = $code;
        }

        return $this->named($company, AccountGroup::class, 'books_group', $node, $name, $values);
    }

    private function inferStandardGroupCode(string $name): ?string
    {
        $n = strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? $name));

        return match (true) {
            str_contains($n, 'sundry creditor') || ($n !== 'creditors' && str_ends_with($n, 'creditors')) || $n === 'creditors' => 'CREDITORS',
            str_contains($n, 'sundry debtor') || ($n !== 'debtors' && str_ends_with($n, 'debtors')) || $n === 'debtors' => 'DEBTORS',
            str_contains($n, 'purchase account') => 'PURCHASE',
            str_contains($n, 'sales account') => 'SALES',
            str_contains($n, 'duties') && str_contains($n, 'tax') => 'DUTIES',
            $n === 'duties & taxes' || $n === 'duties and taxes' => 'DUTIES',
            str_contains($n, 'bank account') || str_contains($n, 'bank od') || str_contains($n, 'bank occ') => 'BANK',
            str_contains($n, 'cash-in-hand') || str_contains($n, 'cash in hand') => 'CASH',
            str_contains($n, 'stock-in-hand') || str_contains($n, 'stock in hand') => 'STOCK',
            str_contains($n, 'fixed asset') => 'FIXED_ASSETS',
            str_contains($n, 'capital account') => 'CAPITAL',
            default => null,
        };
    }

    private function currency(Company $company, \DOMElement $node, string $name): ?\Tally\Models\Currency
    {
        $code = strtoupper(trim($this->text($node, 'ORIGINALNAME') ?: $this->text($node, 'MAILINGNAME') ?: $name));
        $code = preg_replace('/[^A-Z0-9]/', '', $code) ?: 'INR';
        $code = substr($code, 0, 10);
        $symbol = $this->text($node, 'EXPANDEDSYMBOL') ?: $this->text($node, 'SYMBOL') ?: $code;
        $decimals = (int) ($this->text($node, 'DECIMALPLACES') ?: 2);
        $isBase = $this->yes($this->text($node, 'ISUPDATINGCOMPANYINFO')) || strtoupper($code) === 'INR';

        $existing = \Tally\Models\Currency::query()
            ->where('company_id', $company->id)
            ->where(function ($q) use ($name, $code) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhere('code', $code);
            })
            ->first();

        $attrs = [
            'company_id' => $company->id,
            'name' => $name !== '' ? $name : $code,
            'code' => $code,
            'symbol' => $this->truncateField($symbol, 16) ?: $code,
            'decimal_places' => max(0, min(6, $decimals)),
            'exchange_rate' => 1,
            'is_base' => $isBase,
        ];

        if ($existing) {
            $existing->fill($attrs)->save();
            $this->counts['updated']++;

            return $existing;
        }

        $created = \Tally\Models\Currency::query()->create($attrs);
        $this->counts['created']++;

        return $created;
    }

    private function booksPayHead(Company $company, \DOMElement $node, string $name): ?\Tally\Models\PayHead
    {
        $parent = strtolower($this->text($node, 'PARENT'));
        if ($this->partyRoleFromParent($parent) !== null || str_contains($parent, 'bank') || str_contains($parent, 'cash')) {
            return null;
        }
        if (! $this->looksLikePayHead($parent, $name) && ! str_contains($parent, 'indirect') && ! str_contains($parent, 'direct')) {
            return null;
        }

        $nature = str_contains($parent, 'deduct') || str_contains(strtolower($name), 'deduct') ? 'deduction' : 'earning';
        // acct_pay_heads.ledger_id is NOT NULL — link (or create) the matching Books ledger.
        $ledger = $this->findLedgerByName($company, $name);
        if (! $ledger) {
            $ledger = $this->ledger($company, $node, $name);
        }
        if (! $ledger) {
            $this->conflict('pay_heads', $this->text($node, 'GUID'), $name, 'Pay head skipped: matching ledger could not be created.');
            $this->counts['skipped']++;

            return null;
        }

        $existing = \Tally\Models\PayHead::query()
            ->where('company_id', $company->id)
            ->where(function ($q) use ($name, $ledger) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhere('ledger_id', $ledger->id);
            })
            ->first();
        $attrs = [
            'company_id' => $company->id,
            'ledger_id' => $ledger->id,
            'name' => $name,
            'nature' => $nature,
            'calculation' => 'flat',
            'rate_or_amount' => abs($this->leadingNumber($this->text($node, 'OPENINGBALANCE'))),
        ];

        if ($existing) {
            $existing->fill($attrs)->save();
            $this->counts['updated']++;

            return $existing;
        }

        $created = \Tally\Models\PayHead::query()->create($attrs);
        $this->counts['created']++;

        return $created;
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
        $parentCode = $this->inferStandardGroupCode($parentName);
        $parentNature = match ($parentCode) {
            'CREDITORS', 'CAPITAL' => AccountNature::Liability,
            'DEBTORS', 'BANK', 'CASH', 'STOCK', 'FIXED_ASSETS' => AccountNature::Asset,
            'PURCHASE' => AccountNature::Expense,
            'SALES' => AccountNature::Income,
            'DUTIES' => AccountNature::Liability,
            default => AccountNature::Asset,
        };
        $parent = AccountGroup::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => $parentName],
            [
                'nature' => $parentNature,
                'is_active' => true,
                'sort_order' => 0,
                'code' => $parentCode,
            ]
        );
        if ($parentCode && ! $parent->code) {
            // Fill missing standard codes without renaming system groups that already have one.
            $parent->code = $parentCode;
            $parent->save();
        }
        $openingRaw = $this->text($node, 'OPENINGBALANCE');
        $opening = (float) str_replace(',', '', $openingRaw !== '' ? $openingRaw : '0');
        $gstRaw = $this->gstRegistrationTypeFromLedger($node);
        $gst = strtolower($gstRaw);
        $gstType = match (true) {
            str_contains($gst, 'composition') => GstRegistrationType::Composition,
            str_contains($gst, 'unregistered') => GstRegistrationType::Unregistered,
            str_contains($gst, 'consumer') => GstRegistrationType::Consumer,
            default => GstRegistrationType::Regular,
        };
        $gstin = $this->gstinFromLedger($node);
        $pan = $this->panFromLedger($node, $gstin);
        $address = $this->addressLinesFromLedger($node);
        $phone = $this->phoneFromLedger($node);
        $mobile = $this->mobileFromLedger($node);
        $email = $this->emailFromLedger($node);
        $contact = $this->contactPersonFromLedger($node);
        $state = $this->stateFromLedger($node);
        $country = $this->countryFromLedger($node);
        $pincode = $this->pincodeFromLedger($node);
        $mailingName = $this->mailingNameFromLedger($node, $name);
        $creditLimitRaw = $this->text($node, 'CREDITLIMIT');
        $creditDaysRaw = $this->text($node, 'BILLCREDITPERIOD');
        $interestOn = $this->yes($this->text($node, 'ISINTERESTON'));
        $interestRateRaw = $this->text($node, 'INTERESTRATE');

        $ledgerAttrs = [
            'account_group_id' => $parent->id,
            'mailing_name' => $mailingName,
            'address' => $address !== '' ? $address : null,
            'phone' => $this->truncateField($phone, 30),
            'email' => $this->truncateField($email, 120),
            'contact_person' => $this->truncateField($contact !== '' ? $contact : null, 120),
            'gstin' => $this->truncateField($gstin !== '' ? $gstin : null, 20),
            'gst_registration_type' => $gstType,
            'pan' => $pan !== '' ? $pan : null,
            'state' => $state !== '' ? $state : null,
            'country' => $country !== '' ? $country : null,
            'pincode' => $pincode !== '' ? $pincode : null,
            'opening_balance' => abs($opening),
            'opening_balance_type' => $opening < 0 ? OpeningBalanceType::Debit : OpeningBalanceType::Credit,
            'maintain_bill_wise' => $this->yes($this->text($node, 'ISBILLWISEON')),
            'cost_centres_applicable' => $this->yes($this->text($node, 'ISCOSTCENTRESON')),
            'calculate_interest' => $interestOn,
            'is_active' => true,
        ];
        if (trim($creditLimitRaw) !== '') {
            $ledgerAttrs['credit_limit'] = (float) str_replace(',', '', $creditLimitRaw);
        }
        if (trim($creditDaysRaw) !== '') {
            $ledgerAttrs['credit_days'] = (int) $creditDaysRaw;
        }
        if ($interestOn && trim($interestRateRaw) !== '') {
            $ledgerAttrs['interest_rate'] = (float) str_replace(',', '', $interestRateRaw);
        }

        $ledger = $this->named($company, Ledger::class, 'books_ledger', $node, $name, $ledgerAttrs);

        $this->bankAccount($ledger, $node);
        $this->rememberLedgerOpening($ledger);

        $parentLower = strtolower($parentName);
        $partyRole = $this->partyRoleFromParent($parentLower);
        if ($partyRole !== null) {
            $party = Party::query()->firstOrNew(['company_id' => $company->id, 'ledger_id' => $ledger->id]);
            $partyAttrs = [
                'type' => $partyRole === 'supplier' ? 'supplier' : 'customer',
                'legal_name' => $name,
                'contact_person' => $ledger->contact_person,
                'phone' => $ledger->phone,
                'mobile' => $this->truncateField($mobile, 30),
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
            ];
            $party->fill($party->exists ? $this->withoutBlank($partyAttrs) : $partyAttrs);
            $party->save();
            $this->pushToDms($party);
        }

        return $ledger;
    }

    private function rememberLedgerOpening(Ledger $ledger): void
    {
        $year = FinancialYear::query()
            ->where('company_id', $ledger->company_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->first();
        if (! $year) {
            return;
        }

        try {
            app(\Tally\Accounting\LedgerOpeningBook::class)->remember($ledger, $year);
        } catch (\Throwable $exception) {
            // Soft side-effect — ledger itself already saved; do not inflate conflict counters.
            report($exception);
        }
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
        $groupName = $this->text($node, 'PARENT') ?: 'Primary';
        try {
            $groupId = ProductGroup::query()->firstOrCreate(
                ['company_id' => $company->id, 'name' => $groupName],
                ['is_active' => true]
            )->id;
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            $groupId = (int) ProductGroup::query()
                ->where('company_id', $company->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($groupName)])
                ->value('id');
            if ($groupId <= 0) {
                throw new \RuntimeException('Product group collision for '.$groupName.' could not be recovered.');
            }
        }
        $opening = $this->leadingNumber($this->text($node, 'OPENINGBALANCE'));
        $openingRate = $this->leadingNumber($this->text($node, 'OPENINGRATE'));
        $purchaseRate = $this->leadingNumber($this->text($node, 'STANDARDCOST'));
        $salesRate = $this->leadingNumber($this->text($node, 'STANDARDPRICE'));
        if ($purchaseRate <= 0 && $openingRate > 0) {
            $purchaseRate = $openingRate;
        }
        if ($salesRate <= 0 && $openingRate > 0) {
            $salesRate = $openingRate;
        }
        $supply = stripos($this->text($node, 'GSTTYPEOFSUPPLY'), 'service') !== false ? 'service' : 'goods';
        try {
            [$hsnId, $taxRateId] = $this->resolveHsnAndTax($company, $node, $supply);
        } catch (\Throwable $exception) {
            $this->conflict('stock_items', $this->text($node, 'GUID'), $name, 'Tax/HSN skipped: '.$exception->getMessage());
            [$hsnId, $taxRateId] = [null, null];
        }

        $existing = $this->findMaster(Product::class, 'books_stock_item', $node, $company, $name, 'name');
        $values = [
            'product_group_id' => $groupId,
            'primary_unit_id' => $unit->id,
            'description' => $this->text($node, 'DESCRIPTION') ?: null,
            'type_of_supply' => $supply,
            'hsn_sac_id' => $hsnId,
            'tax_rate_id' => $taxRateId,
            'opening_quantity' => $opening,
            'opening_rate' => $openingRate,
            'opening_value' => abs($this->leadingNumber($this->text($node, 'OPENINGVALUE'))),
            'purchase_rate' => $purchaseRate,
            'sales_rate' => $salesRate,
            'reorder_level' => 0,
            'track_batch' => $this->yes($this->text($node, 'ISBATCHWISEON')),
            'is_active' => true,
        ];
        if (! $existing) {
            $partNo = $this->text($node, 'PARTNO') ?: $this->text($node, 'ADDITIONALNAME');
            $code = $partNo !== '' ? $partNo : strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?: 'ITEM', 0, 20));
            while (Product::query()->where('company_id', $company->id)->where('code', $code)->exists()) {
                $code = substr($code, 0, 16).random_int(10, 99);
            }
            $values['code'] = $code;
        }

        $product = $this->named($company, Product::class, 'books_stock_item', $node, $name, $values);

        $this->syncGodownOpeningsFromStockItem($product, $company, $node);

        return $product;
    }

    /**
     * @return array{0: ?int, 1: ?int} hsn_sac_id, tax_rate_id
     */
    private function resolveHsnAndTax(Company $company, \DOMElement $node, string $supply): array
    {
        $hsnCode = $this->firstNestedText($node, 'HSNCODE');
        if ($hsnCode === '') {
            $hsnCode = $this->text($node, 'HSNCODE');
        }
        $hsnCode = strtoupper(trim($hsnCode));

        $igst = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        foreach ($node->getElementsByTagName('RATEDETAILS.LIST') as $rateNode) {
            if (! $rateNode instanceof \DOMElement) {
                continue;
            }
            $head = strtoupper($this->text($rateNode, 'GSTRATEDUTYHEAD'));
            $rate = $this->leadingNumber($this->text($rateNode, 'GSTRATE'));
            if (str_contains($head, 'IGST')) {
                $igst = $rate;
            } elseif (str_contains($head, 'CGST')) {
                $cgst = $rate;
            } elseif (str_contains($head, 'SGST') || str_contains($head, 'UTGST')) {
                $sgst = $rate;
            }
        }
        if ($igst <= 0 && ($cgst + $sgst) > 0) {
            $igst = round($cgst + $sgst, 4);
        }

        $taxRateId = null;
        if ($igst > 0 || $hsnCode !== '') {
            $category = TaxCategory::query()->firstOrCreate(
                ['company_id' => $company->id, 'code' => 'GST'],
                ['name' => 'GST', 'is_active' => true]
            );
            $rateCode = 'GST'.rtrim(rtrim(number_format($igst, 2, '.', ''), '0'), '.');
            $rateCode = str_replace('.', '', $rateCode);
            if ($rateCode === 'GST') {
                $rateCode = 'GST0';
            }
            $rateName = 'GST '.rtrim(rtrim(number_format($igst, 2, '.', ''), '0'), '.').'%';
            $attrs = [
                'tax_category_id' => $category->id,
                'name' => $rateName,
                'cgst_rate' => $cgst > 0 ? $cgst : round($igst / 2, 4),
                'sgst_rate' => $sgst > 0 ? $sgst : round($igst / 2, 4),
                'igst_rate' => $igst,
                'cess_rate' => 0,
                'is_active' => true,
            ];
            // Unique key is company_id + code (not name) — look up by code to avoid GST18 collisions.
            try {
                $taxRate = TaxRate::query()->firstOrCreate(
                    ['company_id' => $company->id, 'code' => $rateCode],
                    $attrs
                );
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $taxRate = TaxRate::query()
                    ->where('company_id', $company->id)
                    ->where('code', $rateCode)
                    ->first()
                    ?: TaxRate::query()
                        ->where('company_id', $company->id)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($rateName)])
                        ->first();
                if (! $taxRate) {
                    throw new \RuntimeException('Tax rate collision for '.$rateCode.' could not be recovered.');
                }
            }
            if ((float) $taxRate->igst_rate !== (float) $igst && $igst > 0) {
                $taxRate->fill([
                    'name' => $rateName,
                    'cgst_rate' => $cgst > 0 ? $cgst : round($igst / 2, 4),
                    'sgst_rate' => $sgst > 0 ? $sgst : round($igst / 2, 4),
                    'igst_rate' => $igst,
                ])->save();
            }
            $taxRateId = $taxRate->id;
        }

        if ($hsnCode === '') {
            return [null, $taxRateId];
        }

        $kind = strlen($hsnCode) >= 6 && $supply === 'service' ? HsnKind::Sac : HsnKind::Hsn;
        $hsn = HsnSac::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => $hsnCode],
            [
                'tax_rate_id' => $taxRateId,
                'kind' => $kind,
                'is_active' => true,
            ]
        );

        return [$hsn->id, $taxRateId ?? $hsn->tax_rate_id];
    }

    private function syncGodownOpeningsFromStockItem(Product $product, Company $company, \DOMElement $node): void
    {
        $rows = [];
        foreach ($node->getElementsByTagName('BATCHALLOCATIONS.LIST') as $batch) {
            if (! $batch instanceof \DOMElement) {
                continue;
            }
            // Only stock-item level openings (not voucher inventory nested under ALLINVENTORYENTRIES).
            $parent = $batch->parentNode;
            while ($parent instanceof \DOMElement) {
                if (strcasecmp($parent->nodeName, 'ALLINVENTORYENTRIES.LIST') === 0) {
                    continue 2;
                }
                if (strcasecmp($parent->nodeName, 'STOCKITEM') === 0) {
                    break;
                }
                $parent = $parent->parentNode;
            }

            $godownName = $this->text($batch, 'GODOWNNAME');
            if ($godownName === '') {
                continue;
            }
            $qty = abs($this->leadingNumber($this->text($batch, 'OPENINGBALANCE') ?: $this->text($batch, 'ACTUALQTY')));
            if ($qty <= 0) {
                continue;
            }
            $godown = Godown::query()->firstOrCreate(
                ['company_id' => $company->id, 'name' => $godownName],
                ['is_active' => true]
            );
            $rate = $this->leadingNumber($this->text($batch, 'OPENINGRATE') ?: $this->text($batch, 'RATE'));
            $rows[] = [
                'godown_id' => $godown->id,
                'batch_name' => trim($this->text($batch, 'BATCHNAME')),
                'quantity' => $qty,
                'rate' => $rate,
                'value' => abs($this->leadingNumber($this->text($batch, 'OPENINGVALUE'))) ?: round($qty * $rate, 2),
            ];
        }

        // Fallback: item-level opening when Tally has no per-godown batch rows.
        if ($rows === []) {
            $qty = abs($this->leadingNumber($this->text($node, 'OPENINGBALANCE')));
            if ($qty > 0) {
                $godown = Godown::query()
                    ->where('company_id', $company->id)
                    ->orderBy('id')
                    ->first()
                    ?: Godown::query()->create([
                        'company_id' => $company->id,
                        'name' => 'Main Location',
                        'is_active' => true,
                    ]);
                $rate = $this->leadingNumber($this->text($node, 'OPENINGRATE'));
                $rows[] = [
                    'godown_id' => $godown->id,
                    'batch_name' => '',
                    'quantity' => $qty,
                    'rate' => $rate,
                    'value' => abs($this->leadingNumber($this->text($node, 'OPENINGVALUE'))) ?: round($qty * $rate, 2),
                ];
            }
        }

        if ($rows === []) {
            return;
        }

        StockGodownOpening::query()
            ->where('product_id', $product->id)
            ->delete();

        foreach ($rows as $row) {
            StockGodownOpening::query()->create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'godown_id' => $row['godown_id'],
                'batch_name' => $row['batch_name'],
                'quantity' => $row['quantity'],
                'rate' => $row['rate'],
                'value' => $row['value'],
            ]);
        }
    }

    private function voucherOrStockJournal(Company $company, \DOMElement $node): Model|null
    {
        $typeName = $this->text($node, 'VOUCHERTYPENAME') ?: $node->getAttribute('VCHTYPE');
        $lower = strtolower(trim(preg_replace('/\s+/', ' ', $typeName) ?? ''));
        if (str_contains($lower, 'stock journal')) {
            return $this->stockJournal($company, $node);
        }
        if (str_contains($lower, 'receipt note') || $lower === 'material in' || str_contains($lower, 'material in')) {
            return $this->receiptNote($company, $node);
        }
        if (
            str_contains($lower, 'quotation')
            || str_contains($lower, 'delivery note')
            || str_contains($lower, 'delivery chall') // covers Tally typo "Delivery Challlan"
            || str_contains($lower, 'sales order')
            || str_contains($lower, 'purchase order')
        ) {
            // Commercial docs land in DMS; quiet on Books accounting path.
            return null;
        }

        return $this->voucher($company, $node);
    }

    private function stockJournal(Company $company, \DOMElement $node): ?StockTransaction
    {
        if ($this->yes($this->text($node, 'ISDELETED')) || $this->yes($this->text($node, 'ISCANCELLED'))) {
            $this->counts['skipped']++;

            return null;
        }

        $number = $this->text($node, 'VOUCHERNUMBER') ?: $node->getAttribute('VCHNUMBER');
        $date = $this->date($this->text($node, 'DATE'));
        $remote = $node->getAttribute('REMOTEID');
        $existing = null;
        if (preg_match('/^books_stock_journal-(\d+)$/', $remote, $match)) {
            $existing = StockTransaction::query()->find((int) $match[1]);
        }
        if (! $existing && $number !== '' && $date) {
            $existing = StockTransaction::query()
                ->where('company_id', $company->id)
                ->where('number', $number)
                ->whereDate('transaction_date', $date)
                ->first();
        }
        if ($existing && BooksSyncQueue::pendingFor($existing)) {
            $this->counts['skipped']++;

            return null;
        }
        if ($existing && $existing->status === VoucherStatus::Posted && ! $this->forceOverwrite) {
            $this->counts['skipped']++;

            return $existing;
        }

        $branch = $company->branches()->where('is_active', true)->orderBy('id')->first()
            ?: $company->branches()->orderBy('id')->first();
        $year = $date ? $this->ensureBooksFinancialYear($company, $date) : null;
        $user = User::query()->orderBy('id')->first();
        if (! $branch || ! $year || ! $user || ! $date) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Books needs a branch, an open financial year, and a user for this stock journal.');

            return null;
        }

        $lines = [];
        $sourceGodownId = null;
        $destinationGodownId = null;
        $lineNo = 0;
        $voucherDest = $this->text($node, 'DESTINATIONGODOWN') ?: $this->text($node, 'DESTINATIONGODOWNNAME');
        $tagged = [
            'ALLINVENTORYENTRIES.LIST' => null,
            'INVENTORYENTRIES.LIST' => null,
            'INVENTORYENTRIESIN.LIST' => true,
            'INVENTORYENTRIESOUT.LIST' => false,
        ];
        foreach ($tagged as $tag => $forceDeemedIn) {
            foreach ($node->getElementsByTagName($tag) as $entry) {
                if (! $entry instanceof \DOMElement || $entry->parentNode !== $node) {
                    continue;
                }
                $product = Product::query()->where('company_id', $company->id)->where('name', $this->text($entry, 'STOCKITEMNAME'))->first();
                if (! $product) {
                    continue;
                }
                $godownName = $this->text($entry, 'GODOWNNAME');
                $batch = $entry->getElementsByTagName('BATCHALLOCATIONS.LIST')->item(0);
                if ($godownName === '' && $batch instanceof \DOMElement) {
                    $godownName = $this->text($batch, 'GODOWNNAME');
                }
                $destName = $this->text($entry, 'DESTINATIONGODOWNNAME');
                if ($destName === '' && $batch instanceof \DOMElement) {
                    $destName = $this->text($batch, 'DESTINATIONGODOWNNAME')
                        ?: $this->text($batch, 'DESTINATIONGODOWN')
                        ?: $this->text($batch, 'TARGETGODOWNNAME');
                }
                if ($destName === '' && $voucherDest !== '') {
                    $destName = $voucherDest;
                }
                $godown = $godownName !== ''
                    ? Godown::query()->firstOrCreate(['company_id' => $company->id, 'name' => $godownName], ['is_active' => true])
                    : null;
                $dest = $destName !== ''
                    ? Godown::query()->firstOrCreate(['company_id' => $company->id, 'name' => $destName], ['is_active' => true])
                    : null;
                $sourceGodownId = $sourceGodownId ?: $godown?->id;
                $destinationGodownId = $destinationGodownId ?: $dest?->id;
                $qtyText = $this->text($entry, 'ACTUALQTY') ?: $this->text($entry, 'BILLEDQTY');
                if ($qtyText === '' && $batch instanceof \DOMElement) {
                    $qtyText = $this->text($batch, 'ACTUALQTY') ?: $this->text($batch, 'BILLEDQTY');
                }
                $qty = abs($this->leadingNumber($qtyText));
                $deemedIn = $forceDeemedIn;
                if ($deemedIn === null) {
                    $deemedIn = $this->yes($this->text($entry, 'ISDEEMEDPOSITIVE'))
                        || $this->text($entry, 'ISDEEMEDPOSITIVE') === '';
                }
                if (! $deemedIn) {
                    $qty = -abs($qty);
                }
                $amount = (float) str_replace(',', '', $this->text($entry, 'AMOUNT'));
                $lineNo++;
                $lines[] = [
                    'line_number' => $lineNo,
                    'product_id' => $product->id,
                    'godown_id' => $godown?->id ?: $sourceGodownId,
                    'quantity' => $qty,
                    'rate' => $this->leadingNumber($this->text($entry, 'RATE')),
                    'value' => abs($amount),
                    'batch_number' => $batch instanceof \DOMElement ? ($this->text($batch, 'BATCHNAME') ?: null) : null,
                ];
            }
        }

        if ($lines === []) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Stock journal has no inventory lines that exist in Books.');

            return null;
        }

        $type = $destinationGodownId && $destinationGodownId !== $sourceGodownId
            ? StockTransactionType::Transfer
            : StockTransactionType::Adjustment;

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $year->id,
            'type' => $type,
            'number' => $number !== '' ? $number : ('SJ-'.uniqid()),
            'transaction_date' => $date,
            'source_godown_id' => $sourceGodownId,
            'destination_godown_id' => $destinationGodownId,
            'narration' => $this->text($node, 'NARRATION') ?: null,
            'status' => VoucherStatus::Posted,
            'created_by' => $user->id,
        ];

        if ($existing) {
            $existing->update($attributes);
            $existing->lines()->delete();
            foreach ($lines as $line) {
                StockTransactionLine::query()->create($line + ['stock_transaction_id' => $existing->id]);
            }
            $this->counts['updated']++;
            $this->link($company, $existing, 'books_stock_journal', $node);

            return $existing->fresh('lines');
        }

        $created = StockTransaction::query()->create($attributes);
        foreach ($lines as $line) {
            StockTransactionLine::query()->create($line + ['stock_transaction_id' => $created->id]);
        }
        $this->counts['created']++;
        $this->link($company, $created, 'books_stock_journal', $node);

        return $created->fresh('lines');
    }

    private function receiptNote(Company $company, \DOMElement $node): ?GoodsReceipt
    {
        if ($this->yes($this->text($node, 'ISDELETED')) || $this->yes($this->text($node, 'ISCANCELLED'))) {
            $this->counts['skipped']++;

            return null;
        }

        $number = $this->text($node, 'VOUCHERNUMBER') ?: $node->getAttribute('VCHNUMBER');
        $date = $this->date($this->text($node, 'DATE'));
        $remote = $node->getAttribute('REMOTEID');
        $existing = null;
        if (preg_match('/^books_goods_receipt-(\d+)$/', $remote, $match)) {
            $existing = GoodsReceipt::query()->find((int) $match[1]);
        }
        if (! $existing && $number !== '' && $date) {
            $existing = GoodsReceipt::query()
                ->where('company_id', $company->id)
                ->where('number', $number)
                ->whereDate('receipt_date', $date)
                ->first();
        }
        if ($existing && $existing->status === 'posted') {
            $this->counts['skipped']++;

            return $existing;
        }

        $branch = $company->branches()->where('is_active', true)->orderBy('id')->first()
            ?: $company->branches()->orderBy('id')->first();
        $year = $date ? $this->ensureBooksFinancialYear($company, $date) : null;
        $user = User::query()->orderBy('id')->first();
        if (! $branch || ! $year || ! $user || ! $date) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Books needs a branch, an open financial year, and a user for this receipt note.');

            return null;
        }

        $partyName = $this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME');
        $supplier = $partyName !== ''
            ? $this->findLedgerByName($company, $partyName)
            : null;

        $lines = [];
        $godownId = null;
        $lineNo = 0;
        foreach ($node->getElementsByTagName('ALLINVENTORYENTRIES.LIST') as $entry) {
            if (! $entry instanceof \DOMElement || $entry->parentNode !== $node) {
                continue;
            }
            $itemName = $this->text($entry, 'STOCKITEMNAME');
            $product = $itemName !== ''
                ? Product::query()->where('company_id', $company->id)->where('name', $itemName)->first()
                : null;
            $batch = $entry->getElementsByTagName('BATCHALLOCATIONS.LIST')->item(0);
            $godownName = $this->text($entry, 'GODOWNNAME');
            if ($godownName === '' && $batch instanceof \DOMElement) {
                $godownName = $this->text($batch, 'GODOWNNAME');
            }
            $godown = $godownName !== ''
                ? Godown::query()->firstOrCreate(['company_id' => $company->id, 'name' => $godownName], ['is_active' => true])
                : null;
            $godownId = $godownId ?: $godown?->id;
            $qty = abs($this->leadingNumber($this->text($entry, 'ACTUALQTY') ?: ($batch instanceof \DOMElement ? $this->text($batch, 'ACTUALQTY') : '')));
            if ($qty <= 0) {
                continue;
            }
            $amount = abs((float) str_replace(',', '', $this->text($entry, 'AMOUNT')));
            $rate = $this->leadingNumber($this->text($entry, 'RATE'));
            if ($rate <= 0 && $qty > 0) {
                $rate = round($amount / $qty, 4);
            }
            $lineNo++;
            $lines[] = [
                'line_number' => $lineNo,
                'product_id' => $product?->id,
                'item_name' => $itemName !== '' ? $itemName : ($product?->name ?? 'Item'),
                'ordered_qty' => $qty,
                'received_qty' => $qty,
                'rate' => $rate,
                'line_total' => round($amount > 0 ? $amount : ($qty * $rate), 2),
            ];
        }

        if ($lines === []) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Receipt Note has no inventory lines that can be imported.');

            return null;
        }

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $year->id,
            'number' => $number !== '' ? mb_substr($number, 0, 30) : ('RN-'.uniqid()),
            'receipt_date' => $date,
            'supplier_ledger_id' => $supplier?->id,
            'godown_id' => $godownId,
            'receipt_kind' => 'purchase',
            'status' => 'draft',
            'narration' => $this->text($node, 'NARRATION') ?: null,
            'created_by' => $user->id,
        ];

        if ($existing) {
            $existing->update($attributes);
            $existing->lines()->delete();
            foreach ($lines as $line) {
                GoodsReceiptLine::query()->create($line + ['goods_receipt_id' => $existing->id]);
            }
            $receipt = $existing->fresh('lines');
            $this->counts['updated']++;
        } else {
            $receipt = GoodsReceipt::query()->create($attributes);
            foreach ($lines as $line) {
                GoodsReceiptLine::query()->create($line + ['goods_receipt_id' => $receipt->id]);
            }
            $receipt = $receipt->fresh('lines');
            $this->counts['created']++;
        }

        $stockLines = $receipt->lines->filter(fn ($line) => (int) ($line->product_id ?? 0) > 0 && (float) $line->received_qty > 0);
        if ($stockLines->isNotEmpty() && $godownId) {
            try {
                $stock = app(StockTransactionService::class)->save(
                    $company,
                    $branch,
                    $year,
                    $user,
                    StockTransactionType::In,
                    [
                        'transaction_date' => $date,
                        'narration' => 'Tally Receipt Note '.$receipt->number.($receipt->narration ? ' — '.$receipt->narration : ''),
                        'lines' => $stockLines->map(fn ($line) => [
                            'product_id' => $line->product_id,
                            'godown_id' => $godownId,
                            'quantity' => rtrim(rtrim(number_format((float) $line->received_qty, 4, '.', ''), '0'), '.') ?: '0',
                            'rate' => number_format((float) $line->rate, 2, '.', ''),
                        ])->values()->all(),
                    ],
                );
                $receipt->update([
                    'status' => 'posted',
                    'stock_transaction_id' => $stock->id,
                ]);
            } catch (\Throwable $exception) {
                $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Receipt Note saved as draft; stock post failed: '.$exception->getMessage());
            }
        } else {
            $receipt->update(['status' => 'posted']);
        }

        $this->link($company, $receipt->fresh(), 'books_goods_receipt', $node);

        return $receipt->fresh('lines');
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
            // Last resort: land unmapped accounting vouchers as Journal so nothing is dropped.
            $type = VoucherType::Journal;
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
            // Posted Books vouchers are immutable in VoucherEngine — never mutate them.
            // With --force, skip quietly (no dual-write conflict spam on re-import).
            if (! $this->forceOverwrite) {
                $link = $this->linkRow($existing, 'books_voucher');
                $localNewer = $link && $existing->updated_at && $link->updated_at && $existing->updated_at->gt($link->updated_at);
                if ($localNewer) {
                    $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Books and Tally both changed '.$number.'. Neither side was overwritten.');
                }
            }
            $this->counts['skipped']++;

            return $existing;
        }

        $entries = $this->voucherEntries($company, $node);
        if ($entries === []) {
            // Quiet skip — missing ledger names are auto-created when possible; empty still means unusable XML.
            $this->counts['skipped']++;

            return null;
        }

        $branch = $company->branches()->where('is_active', true)->orderBy('id')->first() ?: $company->branches()->orderBy('id')->first();
        $year = $date ? $this->ensureBooksFinancialYear($company, $date) : null;
        $user = $existing?->creator ?: User::query()->orderBy('id')->first();
        if (! $branch || ! $year || ! $user || ! $date) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $number, 'Books needs a branch, an open financial year, and a user for this voucher.');

            return null;
        }

        $partyName = $this->text($node, 'PARTYLEDGERNAME');
        $party = $partyName !== '' ? $this->findLedgerByName($company, $partyName) : null;
        $updateTarget = $existing && $existing->isDraft() ? $existing : null;
        try {
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
                'allocations' => $this->voucherBillAllocationRows($company, $node, $entries),
            ], true, $updateTarget, $number !== '' ? $number : null, true);
        } catch (\Throwable $exception) {
            $this->conflict('vouchers', $this->text($node, 'GUID'), $number, $exception->getMessage());

            return null;
        }

        if ($updateTarget) {
            $this->counts['updated']++;
        } else {
            $this->counts['created']++;
        }

        $this->inventoryLines($saved, $company, $node);
        $this->persistInboundBillAllocations($saved, $company, $node);

        return $saved;
    }

    private function findLedgerByName(Company $company, string $name): ?Ledger
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        if ($name === '') {
            return null;
        }

        if ($this->lookupCache) {
            $cached = $this->lookupCache->findNamed(Ledger::class, (int) $company->id, $name);
            if ($cached instanceof Ledger) {
                return $cached;
            }
        }

        $ledger = Ledger::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($name) {
                $query->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
            })
            ->first();

        if ($ledger && $this->lookupCache) {
            $this->lookupCache->rememberLedger((int) $company->id, $name, (int) $ledger->id);
        }

        return $ledger;
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
        // Artisan / --force: Tally XML wins. Connector polls still protect localNewer edits.
        if ($existing && ! $this->forceOverwrite && $this->localNewer($existing, $tallyType)) {
            $this->conflict($tallyType, $this->text($node, 'GUID'), $name, 'Books and Tally both changed '.$name.'. Neither side was overwritten.');
            $this->counts['skipped']++;

            return $existing;
        }

        $values['company_id'] = $company->id;
        if ($nameColumn === 'name') {
            $values['name'] = $name;
        }
        if ($existing) {
            // Keep richer local values when Tally omits a field on re-import.
            $existing->fill($this->withoutBlank($values));
            $existing->save();
            $this->counts['updated']++;
            $this->lookupCache?->rememberModel($class, (int) $company->id, $nameColumn === 'name' ? $name : (string) ($values[$nameColumn] ?? $name), (int) $existing->getKey());

            return $existing;
        }

        try {
            $created = $class::query()->create($values);
            $this->counts['created']++;
            $this->lookupCache?->rememberModel($class, (int) $company->id, $nameColumn === 'name' ? $name : (string) ($values[$nameColumn] ?? $name), (int) $created->getKey());

            return $created;
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Concurrent/re-import race: fetch the colliding row and update it instead of aborting.
            $fallback = $class::query()
                ->where('company_id', $company->id)
                ->whereRaw('LOWER('.$nameColumn.') = ?', [mb_strtolower($name)])
                ->first();
            if ($fallback) {
                $fallback->fill($values)->save();
                $this->counts['updated']++;
                $this->lookupCache?->rememberModel($class, (int) $company->id, $nameColumn === 'name' ? $name : (string) ($values[$nameColumn] ?? $name), (int) $fallback->getKey());

                return $fallback;
            }
            throw new \RuntimeException('Unique constraint on '.$class.' for '.$name.' and row could not be recovered.');
        }
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

        $normalized = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);

        if ($this->lookupCache) {
            $cached = $this->lookupCache->findNamed($class, (int) $company->id, $normalized, $nameColumn);
            if ($cached) {
                return $cached;
            }
        }

        $found = $class::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($nameColumn, $name, $normalized) {
                $query->where($nameColumn, $name)
                    ->orWhere($nameColumn, $normalized)
                    ->orWhereRaw('LOWER('.$nameColumn.') = ?', [mb_strtolower($normalized)]);
            })
            ->first();

        if ($found && $this->lookupCache) {
            $this->lookupCache->rememberModel($class, (int) $company->id, $normalized, (int) $found->getKey());
        }

        return $found;
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
        $rows = $this->collectVoucherEntryRows($company, $node, true);
        // Some Tally exports nest ledger lines; retry without direct-parent filter.
        if ($rows === []) {
            $rows = $this->collectVoucherEntryRows($company, $node, false);
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectVoucherEntryRows(Company $company, \DOMElement $node, bool $directChildrenOnly): array
    {
        $rows = [];
        foreach (['ALLLEDGERENTRIES.LIST', 'LEDGERENTRIES.LIST'] as $tag) {
            foreach ($node->getElementsByTagName($tag) as $entry) {
                if (! $entry instanceof \DOMElement) {
                    continue;
                }
                if ($directChildrenOnly && $entry->parentNode !== $node) {
                    continue;
                }
                $ledgerName = $this->text($entry, 'LEDGERNAME');
                $ledger = $this->findLedgerByName($company, $ledgerName)
                    ?: $this->ensureLedgerFromVoucherLine($company, $ledgerName);
                if (! $ledger) {
                    continue;
                }
                // Tally voucher amounts are already signed (negative = debit, positive = credit).
                $amount = $this->leadingNumber($this->text($entry, 'AMOUNT'));
                if ($amount == 0.0) {
                    continue;
                }
                $centreName = '';
                $centreNode = $entry->getElementsByTagName('COSTCENTREALLOCATIONS.LIST')->item(0);
                if ($centreNode instanceof \DOMElement) {
                    $centreName = $this->text($centreNode, 'NAME');
                }
                $centreId = null;
                if ($centreName !== '') {
                    $centreId = $this->lookupCache?->costCentreId((int) $company->id, $centreName)
                        ?: CostCentre::query()->where('company_id', $company->id)->where('name', $centreName)->value('id');
                    if ($centreId && $this->lookupCache) {
                        $this->lookupCache->rememberCostCentre((int) $company->id, $centreName, (int) $centreId);
                    }
                }
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

    private function ensureLedgerFromVoucherLine(Company $company, string $name): ?Ledger
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        if ($name === '' || str_starts_with($name, "\x04")) {
            return null;
        }

        $existing = $this->findLedgerByName($company, $name);
        if ($existing) {
            return $existing;
        }

        try {
            $group = AccountGroup::query()
                ->where('company_id', $company->id)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) = ?', ['sundry creditors'])
                        ->orWhereRaw('LOWER(name) = ?', ['sundry debtors'])
                        ->orWhereRaw('LOWER(name) = ?', ['indirect expenses']);
                })
                ->orderBy('id')
                ->first()
                ?: AccountGroup::query()->where('company_id', $company->id)->orderBy('id')->first();

            if (! $group) {
                return null;
            }

            $ledger = Ledger::query()->create([
                'company_id' => $company->id,
                'account_group_id' => $group->id,
                'name' => $name,
                'is_active' => true,
            ]);
            $this->counts['created']++;
            $this->lookupCache?->rememberLedger((int) $company->id, $name, (int) $ledger->id);

            return $ledger;
        } catch (\Throwable) {
            return $this->findLedgerByName($company, $name);
        }
    }

    /**
     * Rows for VoucherEngine::allocate (Agst Ref with matching Books bills only).
     *
     * @param  list<array<string, mixed>>  $entries
     * @return list<array{bill_id:int, amount:string}>
     */
    private function voucherBillAllocationRows(Company $company, \DOMElement $node, array $entries): array
    {
        $rows = [];
        foreach ($this->parseBillAllocationLists($company, $node) as $bill) {
            if ($bill['type'] !== 'agst_ref' || $bill['name'] === '' || $bill['ledger_id'] === null) {
                continue;
            }
            $found = Bill::query()
                ->where('company_id', $company->id)
                ->where('ledger_id', $bill['ledger_id'])
                ->where('bill_number', $bill['name'])
                ->first();
            if (! $found) {
                continue;
            }
            $rows[] = [
                'bill_id' => $found->id,
                'amount' => number_format(abs($bill['amount']), 2, '.', ''),
            ];
        }

        return $rows;
    }

    /**
     * Persist Tally bill refs that VoucherEngine could not allocate (New Ref / unmatched Agst Ref).
     */
    private function persistInboundBillAllocations(Voucher $voucher, Company $company, \DOMElement $node): void
    {
        $parsed = $this->parseBillAllocationLists($company, $node);
        if ($parsed === []) {
            return;
        }

        $existing = $voucher->billAllocations()->get();
        $haveBillIds = $existing->pluck('bill_id')->filter()->all();

        foreach ($parsed as $bill) {
            if ($bill['ledger_id'] === null || $bill['name'] === '' || abs($bill['amount']) < 0.01) {
                continue;
            }

            $matched = Bill::query()
                ->where('company_id', $company->id)
                ->where('ledger_id', $bill['ledger_id'])
                ->where('bill_number', $bill['name'])
                ->first();

            if ($matched && in_array($matched->id, $haveBillIds, true)) {
                continue;
            }

            // Rename auto-opened bill (voucher number) to Tally New Ref name when unique.
            if (! $matched && $bill['type'] === 'new_ref') {
                $auto = Bill::query()
                    ->where('company_id', $company->id)
                    ->where('ledger_id', $bill['ledger_id'])
                    ->where('bill_number', $voucher->voucher_number)
                    ->first();
                if ($auto) {
                    $taken = Bill::query()
                        ->where('company_id', $company->id)
                        ->where('ledger_id', $bill['ledger_id'])
                        ->where('bill_number', $bill['name'])
                        ->whereKeyNot($auto->id)
                        ->exists();
                    if (! $taken) {
                        $auto->update(['bill_number' => $bill['name']]);
                        $matched = $auto->fresh();
                    }
                }
            }

            $already = $voucher->billAllocations()
                ->where('ledger_id', $bill['ledger_id'])
                ->where(function ($query) use ($bill, $matched) {
                    if ($matched) {
                        $query->where('bill_id', $matched->id);
                    } else {
                        $query->where('reference_name', $bill['name']);
                    }
                })
                ->exists();
            if ($already) {
                continue;
            }

            BillAllocation::query()->create([
                'company_id' => $company->id,
                'bill_id' => $matched?->id,
                'voucher_id' => $voucher->id,
                'ledger_id' => $bill['ledger_id'],
                'reference_type' => $bill['type'],
                'reference_name' => $bill['name'],
                'amount' => number_format(abs($bill['amount']), 2, '.', ''),
            ]);
        }
    }

    /**
     * @return list<array{ledger_id:?int, name:string, type:string, amount:float}>
     */
    private function parseBillAllocationLists(Company $company, \DOMElement $node): array
    {
        $out = [];
        foreach (['ALLLEDGERENTRIES.LIST', 'LEDGERENTRIES.LIST'] as $tag) {
            foreach ($node->getElementsByTagName($tag) as $entry) {
                if (! $entry instanceof \DOMElement || $entry->parentNode !== $node) {
                    continue;
                }
                $ledgerName = $this->text($entry, 'LEDGERNAME');
                $ledgerId = $ledgerName !== ''
                    ? Ledger::query()->where('company_id', $company->id)->where('name', $ledgerName)->value('id')
                    : null;
                foreach ($entry->getElementsByTagName('BILLALLOCATIONS.LIST') as $billNode) {
                    if (! $billNode instanceof \DOMElement || $billNode->parentNode !== $entry) {
                        continue;
                    }
                    $out[] = [
                        'ledger_id' => $ledgerId ? (int) $ledgerId : null,
                        'name' => $this->text($billNode, 'NAME'),
                        'type' => $this->billReferenceType($this->text($billNode, 'BILLTYPE')),
                        'amount' => (float) str_replace(',', '', $this->text($billNode, 'AMOUNT')),
                    ];
                }
            }
        }

        return $out;
    }

    private function billReferenceType(string $type): string
    {
        $type = strtolower(trim($type));

        return match (true) {
            str_contains($type, 'agst') => 'agst_ref',
            str_contains($type, 'advance') => 'advance',
            str_contains($type, 'on account') => 'on_account',
            default => 'new_ref',
        };
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

    /**
     * Resolve or create an Apr–Mar Books financial year covering $date
     * so Journal/Contra imports are not blocked on missing FY rows.
     */
    private function ensureBooksFinancialYear(Company $company, string $date): FinancialYear
    {
        $existing = FinancialYear::query()
            ->where('company_id', $company->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
        if ($existing) {
            return $existing;
        }

        $carbon = \Illuminate\Support\Carbon::parse($date);
        $start = $carbon->month >= 4
            ? $carbon->copy()->month(4)->day(1)->startOfDay()
            : $carbon->copy()->subYear()->month(4)->day(1)->startOfDay();
        $end = $start->copy()->addYear()->subDay();

        return FinancialYear::query()->create([
            'company_id' => $company->id,
            'name' => FinancialYear::labelForPeriod($start, $end),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'is_active' => true,
            'is_closed' => false,
        ]);
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
            // Soft dual-write — Books row is already the source of truth for this import stage.
            report($exception);
        }
    }

    private function bankAccount(Ledger $ledger, \DOMElement $node): void
    {
        $payment = $this->preferredPaymentDetails($node);
        if (! $payment instanceof \DOMElement) {
            return;
        }

        $bankName = $this->text($payment, 'BANKNAME');
        $account = $this->text($payment, 'ACCOUNTNUMBER');
        $ifsc = $this->text($payment, 'IFSCODE');
        if ($bankName === '' && $account === '' && $ifsc === '') {
            return;
        }

        // Prefer full rows; still keep bank name when account/IFSC incomplete.
        if ($account === '' && $ifsc === '' && $bankName === '') {
            return;
        }

        $ifscNormalized = $ifsc !== ''
            ? strtoupper(preg_replace('/\s+/', '', $ifsc) ?? $ifsc)
            : '';
        if (strlen($ifscNormalized) > 20) {
            $ifscNormalized = substr($ifscNormalized, 0, 20);
        }

        // Books bank accounts historically require account+IFSC for unique rows.
        if ($account === '' || $ifscNormalized === '') {
            return;
        }

        try {
            // Prefer ledger_id; fall back to company+account when unique index is on account_number.
            $existing = BankAccount::query()->where('ledger_id', $ledger->id)->first()
                ?: BankAccount::query()
                    ->where('company_id', $ledger->company_id)
                    ->where('account_number', $account)
                    ->first();

            $attrs = [
                'company_id' => $ledger->company_id,
                'ledger_id' => $ledger->id,
                'bank_name' => $this->truncateField($bankName !== '' ? $bankName : $ledger->name, 120) ?: $ledger->name,
                'account_number' => $this->truncateField($account, 64) ?: $account,
                'ifsc' => $ifscNormalized,
            ];

            if ($existing) {
                $existing->fill($this->withoutBlank($attrs))->save();
            } else {
                BankAccount::query()->create($attrs);
            }
        } catch (\Throwable $exception) {
            // Soft side-effect — ledger itself already saved; do not inflate conflict counters.
            report($exception);
        }
    }

    private function company(): ?Company
    {
        $name = trim((string) config('tally_sync.company', config('services.tally.company', '')));
        if ($name !== '') {
            $found = Company::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->where('is_active', true)->first();
            if ($found) {
                return $found;
            }
        }

        $orgId = \App\Domains\Organization\Models\Company::query()
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id')
            ?: \App\Domains\Organization\Models\Company::query()->orderBy('id')->value('id');
        if ($orgId) {
            $linked = \App\Domains\Sync\Support\BooksCompany::forOrganization((int) $orgId);
            if ($linked) {
                return $linked;
            }
        }

        $active = Company::query()->where('is_active', true)->orderBy('id')->limit(2)->get();

        return $active->count() === 1 ? $active->first() : null;
    }

    private function firstNestedText(\DOMElement $node, string $tag): string
    {
        foreach ($node->getElementsByTagName($tag) as $child) {
            if ($child instanceof \DOMElement) {
                return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $child->textContent) ?? '');
            }
        }

        return '';
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

        if ($this->lookupCache) {
            $cached = match ($class) {
                AccountGroup::class => $this->lookupCache->groupId((int) $company->id, $name),
                ProductGroup::class => $this->lookupCache->productGroupId((int) $company->id, $name),
                Godown::class => $this->lookupCache->godownId((int) $company->id, $name),
                CostCentre::class => $this->lookupCache->costCentreId((int) $company->id, $name),
                default => null,
            };
            if ($cached) {
                return $cached;
            }
        }

        $id = $class::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($name) {
                $query->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
            })
            ->value('id');

        if ($id && $this->lookupCache) {
            $this->lookupCache->rememberModel($class, (int) $company->id, $name, (int) $id);
        }

        return $id;
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
            str_contains($name, 'sales order'), str_contains($name, 'purchase order'),
            str_contains($name, 'quotation'), str_contains($name, 'delivery note'),
            str_contains($name, 'delivery chall'),
            str_contains($name, 'receipt note'), str_contains($name, 'material in') => null, // outbound DMS / dedicated importers
            str_contains($name, 'credit note'), str_contains($name, 'sale return'), str_contains($name, 'sales return') => VoucherType::CreditNote,
            str_contains($name, 'debit note'), str_contains($name, 'purchase return') => VoucherType::DebitNote,
            str_contains($name, 'stock journal') => null, // handled as StockTransaction
            str_contains($name, 'material out'),
            str_contains($name, 'rejection'),
            str_contains($name, 'physical stock'), str_contains($name, 'job work') => VoucherType::Adjustment,
            str_contains($name, 'reversing journal'), str_contains($name, 'memorandum'),
            str_contains($name, 'payroll'), str_contains($name, 'attendance'),
            str_contains($name, 'journal') => VoucherType::Journal,
            str_contains($name, 'payment') => VoucherType::Payment,
            str_contains($name, 'receipt') => VoucherType::Receipt,
            str_contains($name, 'contra') => VoucherType::Contra,
            str_contains($name, 'transfer') => VoucherType::Transfer,
            str_contains($name, 'gst tax invoice'), str_contains($name, 'tax invoice'),
            str_contains($name, 'sales') => VoucherType::Sales,
            str_contains($name, 'purchase') => VoucherType::Purchase,
            default => null,
        };
    }

    private function voucherTypeMaster(Company $company, \DOMElement $node, string $name): ?VoucherTypeMaster
    {
        $category = $this->voucherType($this->text($node, 'PARENT') ?: $name);
        if (! $category) {
            // Delivery Note / Quotation / Stock Journal parents have no Books voucher category.
            return null;
        }

        $existing = VoucherTypeMaster::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($name, $category) {
                $query->where('name', $name)->orWhere('category', $category->value);
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$name])
            ->first();

        if ($existing) {
            $existing->fill([
                'category' => $category->value,
            ])->save();
            $this->counts['updated']++;

            return $existing;
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

    /**
     * Drop null/blank values so re-import does not wipe richer existing fields.
     * Keeps 0 / false intentionally.
     *
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    private function withoutBlank(array $attrs): array
    {
        $out = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    private function cleanXmlText(?string $value): string
    {
        $value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $value) ?? '');
        if ($value === '') {
            return '';
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function isTallyPlaceholder(string $value): bool
    {
        $value = $this->cleanXmlText($value);
        if ($value === '') {
            return true;
        }

        $lower = mb_strtolower($value);
        if (str_contains($lower, 'not applicable')) {
            return true;
        }
        if (preg_match('/^(?:\x04\s*)?any\b/u', $lower) === 1) {
            return true;
        }

        // Tally often prefixes placeholders with control char &#4;
        return str_starts_with($value, "\x04");
    }

    /**
     * First non-empty text among tags (direct children preferred, then descendants).
     *
     * @param  list<string>  $tags
     */
    private function firstNonEmptyText(\DOMElement $node, array $tags, bool $deep = true): string
    {
        foreach ($tags as $tag) {
            $direct = $this->text($node, $tag);
            if ($direct !== '' && ! $this->isTallyPlaceholder($direct)) {
                return $this->cleanXmlText($direct);
            }
        }

        if (! $deep) {
            return '';
        }

        foreach ($tags as $tag) {
            foreach ($node->getElementsByTagName($tag) as $child) {
                if (! $child instanceof \DOMElement) {
                    continue;
                }
                $value = $this->cleanXmlText($child->textContent);
                if ($value !== '' && ! $this->isTallyPlaceholder($value)) {
                    return $value;
                }
            }
        }

        return '';
    }

    private function gstinFromLedger(\DOMElement $node): string
    {
        $direct = $this->text($node, 'PARTYGSTIN');
        if ($direct !== '' && ! $this->isTallyPlaceholder($direct)) {
            return strtoupper($this->cleanXmlText($direct));
        }

        $best = '';
        $bestFrom = '';
        foreach ($node->getElementsByTagName('LEDGSTREGDETAILS.LIST') as $list) {
            if (! $list instanceof \DOMElement) {
                continue;
            }
            $gstin = $this->text($list, 'GSTIN');
            if ($gstin === '' || $this->isTallyPlaceholder($gstin)) {
                continue;
            }
            $from = $this->text($list, 'APPLICABLEFROM');
            if ($best === '' || $from >= $bestFrom) {
                $best = $this->cleanXmlText($gstin);
                $bestFrom = $from;
            }
        }
        if ($best !== '') {
            return strtoupper($best);
        }

        $fallback = $this->firstNonEmptyText($node, ['GSTIN']);

        return $fallback !== '' ? strtoupper($fallback) : '';
    }

    private function gstRegistrationTypeFromLedger(\DOMElement $node): string
    {
        $best = '';
        $bestFrom = '';
        foreach ($node->getElementsByTagName('LEDGSTREGDETAILS.LIST') as $list) {
            if (! $list instanceof \DOMElement) {
                continue;
            }
            $type = $this->text($list, 'GSTREGISTRATIONTYPE');
            if ($type === '' || $this->isTallyPlaceholder($type)) {
                continue;
            }
            $from = $this->text($list, 'APPLICABLEFROM');
            if ($best === '' || $from >= $bestFrom) {
                $best = $this->cleanXmlText($type);
                $bestFrom = $from;
            }
        }
        if ($best !== '') {
            return $best;
        }

        return $this->firstNonEmptyText($node, ['GSTREGISTRATIONTYPE']);
    }

    private function panFromLedger(\DOMElement $node, string $gstin = ''): string
    {
        $pan = $this->firstNonEmptyText($node, ['INCOMETAXNUMBER']);
        if ($pan !== '') {
            return strtoupper($pan);
        }

        $gstin = strtoupper(preg_replace('/\s+/', '', $gstin) ?? $gstin);
        if (preg_match('/^[0-9]{2}([A-Z]{5}[0-9]{4}[A-Z])[A-Z0-9]{3}$/', $gstin, $match)) {
            return $match[1];
        }

        return '';
    }

    private function addressLinesFromLedger(\DOMElement $node): string
    {
        $lines = [];
        foreach ($node->getElementsByTagName('ADDRESS') as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }
            $value = $this->cleanXmlText($child->textContent);
            if ($value === '' || $this->isTallyPlaceholder($value)) {
                continue;
            }
            if (! in_array($value, $lines, true)) {
                $lines[] = $value;
            }
        }

        if ($lines === []) {
            $direct = $this->cleanXmlText($this->text($node, 'ADDRESS'));
            if ($direct !== '' && ! $this->isTallyPlaceholder($direct)) {
                $lines[] = $direct;
            }
        }

        return implode("\n", $lines);
    }

    private function stateFromLedger(\DOMElement $node): string
    {
        foreach (['STATENAME', 'STATE', 'PRIORSTATENAME', 'PLACEOFSUPPLY'] as $tag) {
            $value = $this->firstNonEmptyText($node, [$tag]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function countryFromLedger(\DOMElement $node): string
    {
        return $this->firstNonEmptyText($node, ['COUNTRYNAME', 'COUNTRYOFRESIDENCE', 'COUNTRY']);
    }

    private function pincodeFromLedger(\DOMElement $node): string
    {
        return $this->firstNonEmptyText($node, ['PINCODE']);
    }

    private function phoneFromLedger(\DOMElement $node): ?string
    {
        foreach (['LEDGERPHONE', 'LEDGERMOBILE', 'PHONENUMBER'] as $tag) {
            $raw = $this->firstNonEmptyText($node, [$tag]);
            $phone = $this->firstPhone($raw);
            if ($phone) {
                return $phone;
            }
        }

        return null;
    }

    private function mobileFromLedger(\DOMElement $node): ?string
    {
        foreach (['LEDGERMOBILE', 'PHONENUMBER', 'LEDGERPHONE'] as $tag) {
            $raw = $this->firstNonEmptyText($node, [$tag]);
            $phone = $this->firstPhone($raw);
            if ($phone) {
                return $phone;
            }
        }

        return null;
    }

    private function emailFromLedger(\DOMElement $node): ?string
    {
        $email = $this->firstNonEmptyText($node, ['EMAIL']);

        return $email !== '' ? $email : null;
    }

    private function contactPersonFromLedger(\DOMElement $node): string
    {
        return $this->firstNonEmptyText($node, ['LEDGERCONTACT', 'CONTACTPERSON']);
    }

    private function mailingNameFromLedger(\DOMElement $node, string $fallbackName): string
    {
        $candidates = [];
        $direct = $this->cleanXmlText($this->text($node, 'MAILINGNAME'));
        if ($direct !== '') {
            $candidates[] = $direct;
        }
        foreach ($node->getElementsByTagName('MAILINGNAME') as $child) {
            if ($child instanceof \DOMElement) {
                $value = $this->cleanXmlText($child->textContent);
                if ($value !== '') {
                    $candidates[] = $value;
                }
            }
        }

        $currencyish = ['INR', 'USD', 'EUR', 'GBP', 'AED', '₹', 'RS', 'RS.'];
        foreach ($candidates as $candidate) {
            if ($this->isTallyPlaceholder($candidate)) {
                continue;
            }
            if (in_array(mb_strtoupper($candidate), $currencyish, true)) {
                continue;
            }

            return $candidate;
        }

        return $fallbackName;
    }

    private function preferredPaymentDetails(\DOMElement $node): ?\DOMElement
    {
        $fallback = null;
        $default = null;
        foreach ($node->getElementsByTagName('PAYMENTDETAILS.LIST') as $payment) {
            if (! $payment instanceof \DOMElement) {
                continue;
            }
            $fallback ??= $payment;
            $bankName = $this->text($payment, 'BANKNAME') ?: $this->firstNestedText($payment, 'BANKNAME');
            $account = $this->text($payment, 'ACCOUNTNUMBER') ?: $this->firstNestedText($payment, 'ACCOUNTNUMBER');
            $ifsc = $this->text($payment, 'IFSCODE') ?: $this->firstNestedText($payment, 'IFSCODE');
            $filled = $bankName !== '' || $account !== '' || $ifsc !== '';
            if ($this->yes($this->text($payment, 'SETASDEFAULT')) && $filled) {
                return $payment;
            }
            if ($this->yes($this->text($payment, 'SETASDEFAULT'))) {
                $default ??= $payment;
            }
            if ($filled) {
                return $payment;
            }
        }

        return $default ?? $fallback;
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
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace([',', '₹', 'Rs.', 'Rs', 'INR', ' '], '', $value);
        $value = preg_replace('/[^\d.\-]/', '', $value) ?? '';
        if ($value === '' || $value === '-' || $value === '.') {
            return 0.0;
        }
        if (preg_match('/^-?\d+(?:\.\d+)?/', $value, $match)) {
            return (float) $match[0];
        }

        return 0.0;
    }

    private function firstPhone(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($value === '') {
            return null;
        }

        // Tally often packs multiple numbers ("98111..., 98222..." / "0161-xxx, 230yyyy").
        $parts = preg_split('/[,\/;|]|(?:\s{2,})/', $value) ?: [];
        $first = trim((string) ($parts[0] ?? $value));

        return $first !== '' ? $first : null;
    }

    private function truncateField(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    /**
     * TallyPrime embeds ASCII 0x04 in "Primary" parents as &#4;, which is not a valid
     * XML 1.0 character reference. Strip those (and other illegal controls) so DOM can parse.
     * Also normalize Windows-1252 / mixed encodings common in Tally stock-item exports.
     */
    public static function sanitizeTallyXml(string $xml): string
    {
        // Numeric character references for illegal controls (keep TAB/LF/CR: 9,10,13).
        $xml = preg_replace('/&#0*(?:[0-8]|1[12]|1[4-9]|2[0-9]|3[01]);/i', '', $xml) ?? $xml;
        $xml = preg_replace('/&#x0*(?:[0-8A-Ca-c]|[Bb]|[EeFf]|1[0-9A-Fa-f]);/i', '', $xml) ?? $xml;
        // Literal control bytes that sometimes appear unescaped.
        $xml = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $xml) ?? $xml;

        if (! mb_check_encoding($xml, 'UTF-8')) {
            $converted = @mb_convert_encoding($xml, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
            if (is_string($converted) && $converted !== '') {
                $xml = $converted;
            } else {
                $xml = iconv('UTF-8', 'UTF-8//IGNORE', $xml) ?: $xml;
            }
        }

        // Drop any remaining invalid UTF-8 sequences so libxml can load.
        $xml = iconv('UTF-8', 'UTF-8//IGNORE', $xml) ?: $xml;

        return $xml;
    }

    private function conflict(string $collection, ?string $remote, ?string $name, string $reason): void
    {
        $collection = substr($collection, 0, 40);
        $remote = $remote ? substr($remote, 0, 120) : null;
        $name = $name ? substr($name, 0, 200) : null;
        $reason = substr($reason, 0, 2000);

        // One open row per collection+remote+reason — connector polls must not stack hundreds.
        $existing = DB::table('tally_sync_conflicts')
            ->where('status', 'open')
            ->where('collection', $collection)
            ->when($remote !== null, fn ($q) => $q->where('remote_id', $remote), fn ($q) => $q->whereNull('remote_id'))
            ->where('reason', $reason)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            DB::table('tally_sync_conflicts')->where('id', $existing->id)->update([
                'name' => $name,
                'updated_at' => now(),
            ]);

            // Do not inflate stage conflict counters on re-import of the same open row.
            return;
        }

        $this->counts['conflicts']++;

        DB::table('tally_sync_conflicts')->insert([
            'collection' => $collection,
            'remote_id' => $remote,
            'name' => $name,
            'reason' => $reason,
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
        if ($key === 'vouchers') {
            $this->maxAlter = app(TallyExportBuilder::class)->nextVoucherCursor($this->cursorStart);
        } else {
            $span = match ($key) {
                'ledgers', 'stock_items' => TallyExportBuilder::MASTER_SPAN,
                default => null,
            };
            $until = match ($key) {
                'ledgers', 'stock_items' => TallyExportBuilder::MASTER_SCAN_UNTIL,
                default => 0,
            };
            if ($span !== null && $this->cursorStart < $until) {
                $this->maxAlter = max($this->maxAlter, $this->cursorStart + $span);
            }
        }

        // Normalize to snake_case so Title_Case legacy rows cannot diverge under CI collations.
        DB::table('tally_sync_cursors')->whereRaw('LOWER(collection) = ?', [mb_strtolower($key)])->delete();
        DB::table('tally_sync_cursors')->insert([
            'collection' => $key,
            'alter_id' => $this->maxAlter,
            'updated_at' => now(),
        ]);

        \App\Domains\TallySync\Support\InboundActivityLog::batch($key, $this->counts, $this->maxAlter);

        return $this->counts + ['alter_id' => $this->maxAlter];
    }
}
