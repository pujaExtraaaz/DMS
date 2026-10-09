<?php

namespace Tally\Demo;

use Tally\Inventory\InventoryAccounts;
use Tally\Inventory\StockTransactionService;
use Tally\Inventory\StockTransactionType;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceService;
use Tally\Manufacturing\ManufacturingService;
use Tally\Models\Company;
use Tally\Models\Ledger;
use App\Models\User;
use Tally\Tax\HsnKind;

/**
 * Adds one manufacturing chapter to Harbour Supplies.
 * Purchase of raw materials, a batched component, a BOM, production, then a sale.
 */
class ManufacturingStory
{
    public function ensure(Company $company): void
    {
        if ($company->billsOfMaterials()->where('name', 'Desk lamp assembly')->exists()) {
            return;
        }

        $user = User::query()->first();
        $branch = $company->branches()->where('code', 'HO')->firstOrFail();
        $year = $company->financialYears()->firstOrFail();
        $store = $company->godowns()->where('code', 'MAIN')->firstOrFail();
        $finishedStore = $company->godowns()->firstOrCreate(
            ['code' => 'FG'],
            ['name' => 'Finished Goods', 'is_active' => true],
        );
        $nos = $company->units()->where('symbol', 'Nos')->firstOrFail();
        $metre = $company->units()->firstOrCreate(
            ['symbol' => 'Mtr'],
            ['name' => 'Metres', 'decimal_places' => 2, 'is_active' => true],
        );
        $coil = $company->units()->firstOrCreate(
            ['symbol' => 'Coil'],
            ['name' => 'Coils', 'decimal_places' => 2, 'is_active' => true],
        );
        $group = $company->productGroups()->firstOrCreate(
            ['code' => 'MFG'],
            ['name' => 'Manufacturing', 'is_active' => true],
        );
        $gst = $company->taxRates()->where('code', 'GST18')->firstOrFail();
        $hsn = $company->hsnSacs()->firstOrCreate(
            ['code' => '9405'],
            ['tax_rate_id' => $gst->id, 'kind' => HsnKind::Hsn, 'description' => 'Lamps', 'is_active' => true],
        );
        $make = function (array $attributes) use ($company, $group) {
            return $company->products()->create($attributes + [
                'product_group_id' => $group->id,
                'purchase_rate' => '0',
                'sales_rate' => '0',
                'opening_quantity' => '0',
                'opening_rate' => '0',
                'opening_value' => '0',
                'minimum_stock' => '0',
                'reorder_level' => '0',
                'is_active' => true,
            ]);
        };
        $wire = $make([
            'name' => 'Copper wire', 'code' => 'WIRE', 'primary_unit_id' => $metre->id,
            'alternate_unit_id' => $coil->id, 'conversion_factor' => '10',
            'purchase_rate' => '20', 'reorder_level' => '5', 'maximum_stock' => '200',
        ]);
        $shade = $make([
            'name' => 'Lamp shade', 'code' => 'SHADE', 'primary_unit_id' => $nos->id,
            'purchase_rate' => '40', 'hsn_sac_id' => $hsn->id, 'tax_rate_id' => $gst->id,
        ]);
        $led = $make([
            'name' => 'LED module', 'code' => 'LED', 'primary_unit_id' => $nos->id,
            'purchase_rate' => '80', 'track_batch' => true, 'reorder_level' => '15', 'maximum_stock' => '10',
            'hsn_sac_id' => $hsn->id, 'tax_rate_id' => $gst->id,
        ]);
        $scrap = $make([
            'name' => 'Copper scrap', 'code' => 'SCRAP', 'primary_unit_id' => $metre->id,
            'purchase_rate' => '2',
        ]);
        $lamp = $make([
            'name' => 'Desk lamp', 'code' => 'LAMP', 'primary_unit_id' => $nos->id,
            'purchase_rate' => '0', 'sales_rate' => '900', 'reorder_level' => '10', 'maximum_stock' => '20',
            'hsn_sac_id' => $hsn->id, 'tax_rate_id' => $gst->id,
        ]);

        $mills = Ledger::query()->where('company_id', $company->id)->where('code', 'MILLS')->firstOrFail();
        $city = Ledger::query()->where('company_id', $company->id)->where('code', 'CITY')->firstOrFail();
        $purchase = Ledger::query()->where('company_id', $company->id)->where('code', 'PURCHASE')->firstOrFail();
        $sales = Ledger::query()->where('company_id', $company->id)->where('code', 'SALES')->firstOrFail();
        $accounts = app(InventoryAccounts::class);
        $rawLedger = $accounts->raw($company);
        $finishedLedger = $accounts->finished($company);

        $line = fn ($product, string $qty, string $rate, int $godownId): array => [
            'item_name' => $product->name,
            'product_id' => $product->id,
            'godown_id' => $godownId,
            'tax_rate_id' => $product->tax_rate_id,
            'hsn_sac_id' => $product->hsn_sac_id,
            'quantity' => $qty,
            'rate' => $rate,
            'discount' => '0',
            'tax_amount' => '0',
        ];

        app(InvoiceService::class)->save($company, $branch, $year, $user, InvoiceKind::Purchase, [
            'invoice_date' => '2026-07-20',
            'party_ledger_id' => $mills->id,
            'account_ledger_id' => $purchase->id,
            'narration' => 'Copper wire and lamp shades for production.',
            'lines' => [
                $line($wire, '20', '20', $store->id),
                $line($shade, '20', '40', $store->id),
            ],
        ], true);

        app(StockTransactionService::class)->save($company, $branch, $year, $user, StockTransactionType::In, [
            'transaction_date' => '2026-07-21',
            'narration' => 'LED modules received in batch LOT-LED-1.',
            'lines' => [[
                'product_id' => $led->id,
                'godown_id' => $store->id,
                'quantity' => '20',
                'rate' => '80',
                'batch_number' => 'LOT-LED-1',
                'manufactured_on' => '2026-07-01',
                'expires_on' => '2026-08-20',
            ]],
        ]);

        $bom = $company->billsOfMaterials()->create([
            'finished_product_id' => $lamp->id,
            'name' => 'Desk lamp assembly',
            'wastage_percent' => '0',
            'is_active' => true,
        ]);
        $bom->lines()->create([
            'product_id' => $wire->id, 'unit_id' => $coil->id, 'quantity' => '0.2000', 'wastage_percent' => '5',
        ]);
        $bom->lines()->create([
            'product_id' => $shade->id, 'unit_id' => $nos->id, 'quantity' => '1', 'wastage_percent' => '0',
        ]);
        $bom->lines()->create([
            'product_id' => $led->id, 'unit_id' => $nos->id, 'quantity' => '1', 'wastage_percent' => '0',
        ]);
        $bom->byproducts()->create(['product_id' => $scrap->id, 'quantity' => '0.1000']);

        app(ManufacturingService::class)->produce($company, $branch, $year, $user, [
            'bill_of_material_id' => $bom->id,
            'manufactured_on' => '2026-07-25',
            'quantity' => '8',
            'source_godown_id' => $store->id,
            'destination_godown_id' => $finishedStore->id,
            'finished_ledger_id' => $finishedLedger->id,
            'raw_ledger_id' => $rawLedger->id,
            'narration' => 'Eight desk lamps assembled.',
            'batches' => [$led->id => 'LOT-LED-1'],
        ]);

        app(InvoiceService::class)->save($company, $branch, $year, $user, InvoiceKind::Sales, [
            'invoice_date' => '2026-08-05',
            'party_ledger_id' => $city->id,
            'account_ledger_id' => $sales->id,
            'narration' => 'Desk lamps sold inside Punjab.',
            'lines' => [
                $line($lamp, '2', '900', $finishedStore->id),
            ],
        ], true);
    }
}
