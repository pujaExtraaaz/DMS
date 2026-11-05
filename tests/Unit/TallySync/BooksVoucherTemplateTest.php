<?php

namespace Tests\Unit\TallySync;

use App\Domains\TallySync\Templates\BooksVoucherTemplate;
use App\Domains\TallySync\Templates\MasterTemplates;
use App\Domains\TallySync\Templates\XmlEnvelope;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BooksVoucherTemplateTest extends TestCase
{
    #[DataProvider('voucherTypes')]
    public function test_render_payload_wraps_every_voucher_type(string $type): void
    {
        $xml = (new BooksVoucherTemplate)->renderPayload([
            'type' => $type,
            'number' => 'TST-1',
            'date' => '2026-04-01',
            'narration' => 'Unit test',
            'party' => 'Test Party',
            'entries' => [
                ['ledger' => 'Cash', 'debit' => 100, 'credit' => 0, 'is_party' => false],
                ['ledger' => 'Sales', 'debit' => 0, 'credit' => 100, 'is_party' => false],
            ],
        ]);

        $this->assertStringContainsString('<ENVELOPE>', $xml);
        $this->assertStringContainsString('<REPORTNAME>Vouchers</REPORTNAME>', $xml);
        $this->assertStringContainsString('<VOUCHERNUMBER>TST-1</VOUCHERNUMBER>', $xml);
        $this->assertStringContainsString('<LEDGERNAME>Cash</LEDGERNAME>', $xml);
        $this->assertStringContainsString('<LEDGERNAME>Sales</LEDGERNAME>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public static function voucherTypes(): array
    {
        return [
            ['journal'],
            ['adjustment'],
            ['payment'],
            ['receipt'],
            ['contra'],
            ['transfer'],
            ['sales'],
            ['purchase'],
            ['credit_note'],
            ['debit_note'],
        ];
    }

    public function test_master_templates_produce_parseable_xml(): void
    {
        $masters = new MasterTemplates;

        foreach ([
            $masters->stockItem('Widget', 'Nos', 'Create', null, '1234', 'A widget', 18),
            $masters->ledger('Acme', 'Sundry Debtors'),
            $masters->unit('Nos'),
            $masters->godown('Main'),
        ] as $xml) {
            $this->assertStringContainsString('<REPORTNAME>All Masters</REPORTNAME>', $xml);
            $this->assertTrue((bool) simplexml_load_string($xml));
        }
    }

    public function test_envelope_helpers(): void
    {
        $v = XmlEnvelope::vouchers('TEST', '<VOUCHER></VOUCHER>', 'Demo Co');
        $m = XmlEnvelope::masters('TEST', '<LEDGER></LEDGER>', 'Demo Co');

        $this->assertStringContainsString('<SVCURRENTCOMPANY>Demo Co</SVCURRENTCOMPANY>', $v);
        $this->assertStringContainsString('<SVCURRENTCOMPANY>Demo Co</SVCURRENTCOMPANY>', $m);
    }

    public function test_journal_memo_uses_memorandum_voucher_type(): void
    {
        $xml = (new BooksVoucherTemplate)->renderPayload([
            'type' => 'journal',
            'number' => 'MEMO-1',
            'date' => '2026-04-01',
            'is_memo' => true,
            'entries' => [
                ['ledger' => 'Cash', 'debit' => 50, 'credit' => 0],
                ['ledger' => 'Expense', 'debit' => 0, 'credit' => 50],
            ],
        ]);

        $this->assertStringContainsString('VCHTYPE="Memorandum"', $xml);
        $this->assertStringContainsString('<VOUCHERTYPENAME>Memorandum</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<ISMEMO>Yes</ISMEMO>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_payment_payload_includes_agst_ref_and_bank_allocation(): void
    {
        $xml = (new BooksVoucherTemplate)->renderPayload([
            'type' => 'payment',
            'number' => 'PAY-W4-1',
            'date' => '2026-04-12',
            'party' => 'Wave Supplier',
            'narration' => 'Supplier payment',
            'entries' => [
                [
                    'ledger' => 'Wave Supplier',
                    'debit' => 1180,
                    'credit' => 0,
                    'is_party' => true,
                    'bills' => [[
                        'name' => 'PINV-100',
                        'type' => 'Agst Ref',
                        'amount' => 1180,
                    ]],
                ],
                [
                    'ledger' => 'HDFC Bank',
                    'debit' => 0,
                    'credit' => 1180,
                    'is_bank' => true,
                    'bank_transaction_type' => 'neft',
                    'instrument_date' => '2026-04-12',
                    'payment_favouring' => 'Wave Supplier',
                ],
            ],
        ]);

        $this->assertStringContainsString('VCHTYPE="Payment"', $xml);
        $this->assertStringContainsString('<BILLTYPE>Agst Ref</BILLTYPE>', $xml);
        $this->assertStringContainsString('<NAME>PINV-100</NAME>', $xml);
        $this->assertStringContainsString('<BANKALLOCATIONS.LIST>', $xml);
        $this->assertStringContainsString('<TRANSACTIONTYPE>e-Fund Transfer</TRANSACTIONTYPE>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_stock_journal_template_exports_inventory_voucher(): void
    {
        $txn = new \Tally\Models\StockTransaction([
            'id' => 99,
            'number' => 'STA-99',
            'transaction_date' => '2026-04-10',
            'type' => \Tally\Inventory\StockTransactionType::Adjustment,
            'narration' => 'Cycle count',
        ]);
        $txn->setRelation('sourceGodown', new \Tally\Models\Godown(['name' => 'Main Location']));
        $txn->setRelation('destinationGodown', null);
        $product = new \Tally\Models\Product(['name' => 'Widget']);
        $product->setRelation('primaryUnit', new \Tally\Models\Unit(['symbol' => 'Nos', 'name' => 'Nos']));
        $line = new \Tally\Models\StockTransactionLine([
            'quantity' => 5,
            'rate' => 10,
            'value' => 50,
            'batch_number' => 'B1',
        ]);
        $line->setRelation('product', $product);
        $line->setRelation('godown', new \Tally\Models\Godown(['name' => 'Main Location']));
        $txn->setRelation('lines', collect([$line]));

        $xml = (new \App\Domains\TallySync\Templates\StockJournalTemplate)->render($txn, 'Create');

        $this->assertStringContainsString('VCHTYPE="Stock Journal"', $xml);
        $this->assertStringContainsString('<VOUCHERTYPENAME>Stock Journal</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<STOCKITEMNAME>Widget</STOCKITEMNAME>', $xml);
        $this->assertStringContainsString('<GODOWNNAME>Main Location</GODOWNNAME>', $xml);
        $this->assertStringContainsString('<BATCHNAME>B1</BATCHNAME>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_journal_reversing_uses_reversal_date(): void
    {
        $xml = (new BooksVoucherTemplate)->renderPayload([
            'type' => 'journal',
            'number' => 'REV-1',
            'date' => '2026-04-01',
            'reverses_on' => '2026-04-30',
            'entries' => [
                ['ledger' => 'Cash', 'debit' => 50, 'credit' => 0],
                ['ledger' => 'Expense', 'debit' => 0, 'credit' => 50],
            ],
        ]);

        $this->assertStringContainsString('VCHTYPE="Reversing Journal"', $xml);
        $this->assertStringContainsString('<VOUCHERTYPENAME>Reversing Journal</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<REVERSALDATE>20260430</REVERSALDATE>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }
}
