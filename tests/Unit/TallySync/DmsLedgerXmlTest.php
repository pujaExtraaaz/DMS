<?php

namespace Tests\Unit\TallySync;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Organization\Models\Company;
use App\Domains\Tally\Services\TallyExportService;
use App\Domains\Tally\Services\TallySyncMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DmsLedgerXmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_ledger_xml_includes_party_contact_credit_and_bank(): void
    {
        $company = Company::query()->create([
            'name' => 'DMS Wave1 Co',
            'code' => 'DW1',
            'is_active' => true,
        ]);

        $type = CustomerType::query()->create([
            'name' => 'Dealer',
            'code' => 'DLR',
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'customer_type_id' => $type->id,
            'name' => 'Wave One Party',
            'trade_name' => 'Wave Trade',
            'code' => 'P-W1',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'phone' => '9999999999',
            'email' => 'party@example.com',
            'address' => 'Fallback address',
            'gstin' => '27AAAAA0000A1Z5',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'credit_limit' => 25000,
            'credit_days' => 45,
            'interest_rate' => 12.5,
            'is_active' => true,
        ]);

        PartyBankAccount::query()->create([
            'customer_id' => $customer->id,
            'account_holder_name' => 'Wave One Party',
            'bank_name' => 'ICICI Bank',
            'branch_name' => 'Pune',
            'account_number' => '998877',
            'account_type' => 'current',
            'ifsc_code' => 'ICIC0001234',
            'is_primary' => true,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildLedgerXml($customer);

        $this->assertStringContainsString('<PARENT>Sundry Debtors</PARENT>', $xml);
        $this->assertStringContainsString('<MAILINGNAME>Wave Trade</MAILINGNAME>', $xml);
        $this->assertStringContainsString('<LEDGERPHONE>9999999999</LEDGERPHONE>', $xml);
        $this->assertStringContainsString('<EMAIL>party@example.com</EMAIL>', $xml);
        $this->assertStringContainsString('<PINCODE>411001</PINCODE>', $xml);
        $this->assertStringContainsString('<CREDITLIMIT>25000.00</CREDITLIMIT>', $xml);
        $this->assertStringContainsString('<BILLCREDITPERIOD>45 Days</BILLCREDITPERIOD>', $xml);
        $this->assertStringContainsString('<ISINTERESTON>Yes</ISINTERESTON>', $xml);
        $this->assertStringContainsString('<INTERESTRATE>12.50</INTERESTRATE>', $xml);
        $this->assertMatchesRegularExpression('/<BANKNAME>ICICI Bank<\/BANKNAME>/i', $xml);
        $this->assertStringContainsString('<IFSCODE>ICIC0001234</IFSCODE>', $xml);
        $this->assertStringContainsString('<GSTREGISTRATIONTYPE>Regular</GSTREGISTRATIONTYPE>', $xml);
        $this->assertStringContainsString('<PARTYGSTIN>27AAAAA0000A1Z5</PARTYGSTIN>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_build_ledger_xml_marks_unregistered_without_gstin(): void
    {
        $company = Company::query()->create([
            'name' => 'DMS Wave1 B',
            'code' => 'DW1B',
            'is_active' => true,
        ]);

        $type = CustomerType::query()->create([
            'name' => 'Retail',
            'code' => 'RTL',
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'customer_type_id' => $type->id,
            'name' => 'Cash Party',
            'code' => 'P-CASH',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildLedgerXml($customer);

        $this->assertStringContainsString('<PARENT>Sundry Creditors</PARENT>', $xml);
        $this->assertStringContainsString('<GSTREGISTRATIONTYPE>Unregistered</GSTREGISTRATIONTYPE>', $xml);
    }
}
