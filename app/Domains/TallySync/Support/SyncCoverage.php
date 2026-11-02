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
                'category' => 'Inventory',
                'document' => 'Stock transfer',
                'queue_type' => 'books_stock_journal',
                'tally_type' => 'Stock Journal',
                'trigger' => 'Posted transfer',
                'note' => 'Books StockTransaction (Transfer)',
            ],
            [
                'direction' => 'outbound',
                'category' => 'Inventory',
                'document' => 'Stock adjustment',
                'queue_type' => 'books_stock_journal',
                'tally_type' => 'Stock Journal',
                'trigger' => 'Posted adjustment',
                'note' => 'Books twin must be Posted to enqueue',
            ],

            // Inbound
            [
                'direction' => 'inbound',
                'category' => 'Master',
                'document' => 'Groups, ledgers, units, godowns, stock items',
                'queue_type' => '— (pull)',
                'tally_type' => 'Masters export',
                'trigger' => 'Connector poll',
                'note' => 'Creates / updates Books masters',
            ],
            [
                'direction' => 'inbound',
                'category' => 'Voucher',
                'document' => 'Sales, Purchase, Receipt, Payment, Contra, Journal, Credit/Debit Note',
                'queue_type' => '— (pull)',
                'tally_type' => 'Matching VCHTYPE',
                'trigger' => 'Connector poll',
                'note' => 'Posted into Books day book',
            ],
            [
                'direction' => 'inbound',
                'category' => 'Inventory',
                'document' => 'Stock Journal',
                'queue_type' => '— (pull)',
                'tally_type' => 'Stock Journal',
                'trigger' => 'Connector poll',
                'note' => 'Books StockTransaction (not a ledger voucher)',
            ],
            [
                'direction' => 'inbound',
                'category' => 'Inventory',
                'document' => 'Receipt Note',
                'queue_type' => '— (pull)',
                'tally_type' => 'Receipt Note',
                'trigger' => 'Connector poll',
                'note' => 'Books Goods Receipt + stock-in — never a cash Receipt',
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

    public static function labelForQueueType(?string $type): string
    {
        if ($type === null || $type === '') {
            return '—';
        }

        return self::queueTypeLabels()[$type] ?? str_replace('_', ' ', $type);
    }
}
