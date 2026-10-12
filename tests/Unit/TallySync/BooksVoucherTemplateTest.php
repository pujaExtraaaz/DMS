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
}
