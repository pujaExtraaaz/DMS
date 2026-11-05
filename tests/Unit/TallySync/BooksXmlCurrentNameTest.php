<?php

namespace Tests\Unit\TallySync;

use App\Domains\Tally\Models\TallySyncMapping;
use App\Domains\TallySync\Queue\BooksXml;
use App\Domains\TallySync\Templates\MasterTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Tally\Models\Ledger;

class BooksXmlCurrentNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_alter_ledger_xml_uses_mapped_tally_name_as_name_attribute(): void
    {
        $company = Company::provision([
            'name' => 'Wave1 Co',
            'country' => 'India',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'is_active' => true,
        ]);

        $group = AccountGroup::query()
            ->where('company_id', $company->id)
            ->where('code', 'DEBTORS')
            ->firstOrFail();

        $ledger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $group->id,
            'name' => 'Renamed Party Pvt Ltd',
            'mailing_name' => 'Renamed Trade',
            'opening_balance' => 0,
            'opening_balance_type' => \Tally\Accounting\OpeningBalanceType::Debit,
            'is_active' => true,
        ]);

        TallySyncMapping::query()->create([
            'entity_type' => Ledger::class,
            'entity_id' => $ledger->id,
            'tally_type' => 'books_ledger',
            'tally_guid' => 'guid-1',
            'tally_name' => 'Old Party Name',
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $xml = (new BooksXml(new MasterTemplates))->render($ledger, 'Alter');

        $this->assertStringContainsString('NAME="Old Party Name"', $xml);
        $this->assertStringContainsString('<NAME>Renamed Party Pvt Ltd</NAME>', $xml);
        $this->assertStringContainsString('ACTION="Alter"', $xml);
        $this->assertStringContainsString('<MAILINGNAME>Renamed Trade</MAILINGNAME>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_master_template_ledger_includes_credit_and_contact_fields(): void
    {
        $xml = (new MasterTemplates)->ledger(
            'Acme Traders',
            'Sundry Debtors',
            'Create',
            null,
            '27AAAAA0000A1Z5',
            '12 MG Road, Pune',
            'Maharashtra',
            [
                'MAILINGNAME' => 'Acme Trade',
                'PINCODE' => '411001',
                'LEDGERPHONE' => '9876543210',
                'EMAIL' => 'a@example.com',
                'GSTREGISTRATIONTYPE' => 'Regular',
                'CREDITLIMIT' => '50000.00',
                'BILLCREDITPERIOD' => '30 Days',
                'BANKNAME' => 'HDFC',
                'ACCOUNTNUMBER' => '123456',
                'IFSCODE' => 'HDFC0001',
            ],
        );

        $this->assertStringContainsString('<MAILINGNAME>Acme Trade</MAILINGNAME>', $xml);
        $this->assertStringContainsString('<PINCODE>411001</PINCODE>', $xml);
        $this->assertStringContainsString('<LEDGERPHONE>9876543210</LEDGERPHONE>', $xml);
        $this->assertStringContainsString('<CREDITLIMIT>50000.00</CREDITLIMIT>', $xml);
        $this->assertStringContainsString('<BILLCREDITPERIOD>30 Days</BILLCREDITPERIOD>', $xml);
        $this->assertStringContainsString('<BANKNAME>HDFC</BANKNAME>', $xml);
        $this->assertStringContainsString('<GSTREGISTRATIONTYPE>Regular</GSTREGISTRATIONTYPE>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }
}
