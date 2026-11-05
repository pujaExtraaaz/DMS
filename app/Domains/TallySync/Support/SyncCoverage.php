<?php

namespace App\Domains\TallySync\Support;

/**
 * Human-readable map of what the connector moves between DMS/Books and TallyPrime.
 *
 * @phpstan-type CoverageRow array{
 *     direction: 'outbound'|'inbound'|'skip',
 *     category: string,
 *     document: string,
 *     queue_type: string,
 *     tally_type: string,
 *     trigger: string,
 *     note: string
 * }
 */
class SyncCoverage
{
    /**
     * @return list<CoverageRow>
     */
    public static function rows(): array
    {
        return [
            // Masters — outbound
            [
                'direction' => 'outbound',
                'category' => 'Master',
                'document' => 'Stock item (Product)',
                'queue_type' => 'product / books_stock_item',
                'tally_type' => 'Stock Item',
                'trigger' => 'Save product',
                'note' => 'HSN, GST, unit, godown openings when mapped',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Master',
                'document' => 'Party ledger (Customer / Supplier)',
                'queue_type' => 'customer / books_ledger',
                'tally_type' => 'Ledger',
                'trigger' => 'Save party',
                'note' => 'GSTIN, address, bill-wise on',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Master',
                'document' => 'Unit of measure',
                'queue_type' => 'uom / books_unit',
                'tally_type' => 'Unit',
                'trigger' => 'Save UOM',
                'note' => 'Symbol / reporting UQC',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Master',
                'document' => 'Warehouse / Godown',
                'queue_type' => 'godown / books_godown',
                'tally_type' => 'Godown',
                'trigger' => 'Save warehouse',
                'note' => 'Books godown when linked',
            ],

            // Commercial vouchers — outbound
            [
                'direction' => 'outbound',
                'category' => 'Sales',
                'document' => 'Tax invoice',
                'queue_type' => 'invoice / books_voucher',
                'tally_type' => 'Sales',
                'trigger' => 'Issued / posted',
                'note' => 'Prefers Books voucher twin when present',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Sales',
                'document' => 'Sales order',
                'queue_type' => 'sales_order',
                'tally_type' => 'Sales Order',
                'trigger' => 'Status = approved',
                'note' => 'DMS-owned; not imported back as voucher',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Sales',
                'document' => 'Quotation',
                'queue_type' => 'quotation',
                'tally_type' => 'Quotation',
                'trigger' => 'Status = sent or accepted',
                'note' => 'Marked optional in Tally (ISOPTIONAL)',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Sales',
                'document' => 'Credit note',
                'queue_type' => 'credit_note / books_voucher',
                'tally_type' => 'Credit Note',
                'trigger' => 'Posted',
                'note' => 'Via Books twin when linked',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Collection',
                'document' => 'Customer receipt / payment',
                'queue_type' => 'payment / books_voucher',
                'tally_type' => 'Receipt',
                'trigger' => 'Completed / paid',
                'note' => 'Cash or bank ledger from method',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Collection',
                'document' => 'Standalone cheque',
                'queue_type' => 'books_voucher',
                'tally_type' => 'Receipt or Payment',
                'trigger' => 'Cleared / deposited',
                'note' => 'Skipped if already linked to a payment',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Purchase',
                'document' => 'Purchase invoice',
                'queue_type' => 'purchase_invoice / books_voucher',
                'tally_type' => 'Purchase',
                'trigger' => 'Posted',
                'note' => 'Accounting purchase; stock often via GRN',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Purchase',
                'document' => 'Purchase order',
                'queue_type' => 'purchase_order',
                'tally_type' => 'Purchase Order',
                'trigger' => 'Status = approved',
                'note' => 'DMS-owned; not imported back as voucher',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Purchase',
                'document' => 'Goods receipt (inward)',
                'queue_type' => 'goods_receipt',
                'tally_type' => 'Receipt Note',
                'trigger' => 'Posted / completed / received',
                'note' => 'Stock receipt — not a cash Receipt',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Purchase',
                'document' => 'Freight bill / landed cost',
                'queue_type' => 'books_voucher',
                'tally_type' => 'Journal',
                'trigger' => 'Posted / allocated',
                'note' => 'Books LandedCostService posts journal first',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Master',
                'document' => 'Brand / Category / SubCategory / Product type',
                'queue_type' => 'brand / category / sub_category / product_type',
                'tally_type' => 'Stock Group',
                'trigger' => 'Save catalog master',
                'note' => 'Nested PARENT chain; product uses deepest group',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Master',
                'document' => 'Branch',
                'queue_type' => 'branch',
                'tally_type' => 'Cost Centre',
                'trigger' => 'Save branch',
                'note' => 'Under Primary Cost Category',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Master',
                'document' => 'Tax rate',
                'queue_type' => 'tax_rate',
                'tally_type' => 'Ledger (Duties & Taxes)',
                'trigger' => 'Save tax rate',
                'note' => 'GST duty ledger; item GST still on stock item',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Inventory',
                'document' => 'Stock transfer',
                'queue_type' => 'stock_transfer',
                'tally_type' => 'Stock Journal',
                'trigger' => 'Completed / received / posted',
                'note' => 'DMS StockTransfer → Stock Journal with destination godown',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Inventory',
                'document' => 'Stock adjustment',
                'queue_type' => 'stock_adjustment',
                'tally_type' => 'Stock Journal',
                'trigger' => 'Posted / approved / completed',
                'note' => 'DMS StockAdjustment → Stock Journal',
            ],

            // Inbound
            [
                'direction' => 'inbound',
                'category' => 'Master',
                'document' => 'Stock groups, units, godowns, ledgers, stock items, cost centres',
                'queue_type' => '— (pull)',
                'tally_type' => 'Masters export',
                'trigger' => 'Connector poll',
                'note' => 'Creates / updates DMS Brand/Category/SubCategory, UOM, Warehouse, Customer(+address/bank), Product, Branch',
            ],
            [
                'direction' => 'inbound',
                'category' => 'Voucher',
                'document' => 'Sales, Purchase, Receipt, Payment, Credit Note, Orders, Quotation',
                'queue_type' => '— (pull)',
                'tally_type' => 'Matching VCHTYPE',
                'trigger' => 'Connector poll',
                'note' => 'Creates DMS docs; skips REMOTEID dms-* (already sent from DMS). Duplicates update by number.',
            ],
            [
                'direction' => 'inbound',
                'category' => 'Inventory',
                'document' => 'Stock Journal',
                'queue_type' => '— (pull)',
                'tally_type' => 'Stock Journal',
                'trigger' => 'Connector poll',
                'note' => 'Transfer → StockTransfer; else StockAdjustment',
            ],
            [
                'direction' => 'inbound',
                'category' => 'Inventory',
                'document' => 'Receipt Note',
                'queue_type' => '— (pull)',
                'tally_type' => 'Receipt Note',
                'trigger' => 'Connector poll',
                'note' => 'DMS PurchaseInward',
            ],
            [
                'direction' => 'skip',
                'category' => 'Master',
                'document' => 'Financial year',
                'queue_type' => '—',
                'tally_type' => 'Company period (Tally UI)',
                'trigger' => 'Voucher import date',
                'note' => 'Tally has no AlterID FY master; DMS FY is auto-created from voucher dates (Apr–Mar)',
            ],
            [
                'direction' => 'inbound',
                'category' => 'Reports',
                'document' => 'Day book, P&L, Trial Balance, Balance Sheet, GST packs',
                'queue_type' => '— (pull)',
                'tally_type' => 'Report snapshots',
                'trigger' => 'Connector poll',
                'note' => 'Stored for review on this screen',
            ],

            // Explicit skips
            [
                'direction' => 'skip',
                'category' => 'Skipped',
                'document' => 'Sales Order / Purchase Order / Quotation (from Tally)',
                'queue_type' => '—',
                'tally_type' => 'Order / Quotation',
                'trigger' => 'Inbound',
                'note' => 'DMS owns these; import skipped',
            ],
            [
                'direction' => 'skip',
                'category' => 'Skipped',
                'document' => 'Delivery Note',
                'queue_type' => '—',
                'tally_type' => 'Delivery Note',
                'trigger' => 'Inbound',
                'note' => 'Not mapped to Adjustment or payment',
            ],
            [
                'direction' => 'skip',
                'category' => 'Skipped',
                'document' => 'Delivery / Settlement / Load sheet / Outstanding ledger',
                'queue_type' => '—',
                'tally_type' => '—',
                'trigger' => 'Outbound',
                'note' => 'No separate queue row; reuse invoice / payment already synced',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function queueTypeLabels(): array
    {
        return [
            'invoice' => 'Sales invoice',
            'payment' => 'Receipt / payment',
            'credit_note' => 'Credit note',
            'purchase_invoice' => 'Purchase invoice',
            'purchase_order' => 'Purchase order',
            'sales_order' => 'Sales order',
            'quotation' => 'Quotation',
            'goods_receipt' => 'Receipt note (GRN)',
            'product' => 'Stock item',
            'customer' => 'Party ledger',
            'uom' => 'Unit',
            'godown' => 'Godown',
            'brand' => 'Brand',
            'category' => 'Category',
            'sub_category' => 'Sub category',
            'product_type' => 'Product type',
            'branch' => 'Branch',
            'tax_rate' => 'Tax rate',
            'stock_transfer' => 'Stock transfer',
            'stock_adjustment' => 'Stock adjustment',
            'books_voucher' => 'Books voucher',
            'books_stock_journal' => 'Books stock journal',
            'books_ledger' => 'Books ledger',
            'books_group' => 'Books group',
            'books_stock_item' => 'Books stock item',
            'books_stock_group' => 'Books stock group',
            'books_godown' => 'Books godown',
            'books_unit' => 'Books unit',
            'books_cost_centre' => 'Cost centre',
            'books_cost_category' => 'Cost category',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function inboundCollectionLabels(): array
    {
        return [
            'stock_items' => 'Stock item',
            'stock_item' => 'Stock item',
            'ledgers' => 'Party ledger',
            'ledger' => 'Party ledger',
            'units' => 'Unit',
            'uom' => 'Unit',
            'godowns' => 'Godown',
            'godown' => 'Godown',
            'stock_groups' => 'Stock group',
            'stock_group' => 'Stock group',
            'cost_centres' => 'Branch',
            'cost_centre' => 'Branch',
            'vouchers' => 'Voucher',
            'voucher' => 'Voucher',
            'groups' => 'Group',
            'bank_accounts' => 'Bank account',
            'voucher_types' => 'Voucher type',
        ];
    }

    public static function labelForQueueType(?string $type): string
    {
        if ($type === null || $type === '') {
            return '—';
        }

        return self::queueTypeLabels()[$type] ?? str_replace('_', ' ', $type);
    }

    public static function labelForInboundCollection(?string $collection): string
    {
        if ($collection === null || $collection === '') {
            return '—';
        }

        $key = strtolower($collection);

        return self::inboundCollectionLabels()[$key] ?? str_replace('_', ' ', $key);
    }

    public static function formatSyncItem(string $typeLabel, ?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return $typeLabel;
        }

        return $typeLabel.' — '.$name;
    }
}
