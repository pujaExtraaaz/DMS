<?php

namespace Tests\Unit\Tally\Banking;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Banking\BankFeedService;
use Tally\Banking\BankStatementImporter;
use Tally\Banking\ElectronicPaymentService;
use Tally\Banking\NeftRtgsPaymentFileService;
use Tally\Models\BankAccount;
use Tally\Models\BankStatementLine;
use Tally\Models\IntegrationEndpoint;

class BankFeedAndPaymentFileTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_ofx_import_creates_statement_lines(): void
    {
        $company = $this->makeCompany();
        $account = $this->makeBankAccount($company, 'Test Bank', '123456', 'HDFC0001234');

        $path = tempnam(sys_get_temp_dir(), 'ofx');
        file_put_contents($path, <<<'OFX'
OFXHEADER:100
<OFX><BANKMSGSRSV1><STMTTRNRS><STMTRS><BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20250415
<TRNAMT>-100.50
<FITID>TX1
<MEMO>Vendor payment
</STMTTRN>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20250416
<TRNAMT>250.00
<FITID>TX2
<NAME>Customer receipt
</STMTTRN>
</BANKTRANLIST></STMTRS></STMTTRNRS></BANKMSGSRSV1></OFX>
OFX);

        $result = app(BankStatementImporter::class)->importPath($account, $path, 'ofx');
        @unlink($path);

        $this->assertSame(2, $result['imported']);
        $this->assertSame(2, BankStatementLine::query()->where('bank_account_id', $account->id)->count());
        $this->assertSame('100.50', (string) BankStatementLine::query()->where('transaction_id', 'TX1')->value('debit'));
    }

    public function test_bank_feed_pulls_csv_from_url(): void
    {
        $company = $this->makeCompany();
        $account = $this->makeBankAccount($company, 'Feed Bank', '999', 'SBIN0001111');

        Http::fake([
            'https://feeds.example/statement.csv' => Http::response(
                "Date,Narration,Debit,Credit\n2025-04-20,Auto feed line,10.00,0.00\n",
                200
            ),
        ]);

        $endpoint = IntegrationEndpoint::query()->create([
            'company_id' => $company->id,
            'name' => 'CSV Feed',
            'kind' => 'bank_feed',
            'url' => 'https://feeds.example/statement.csv',
            'bank_account_id' => $account->id,
            'is_active' => true,
        ]);

        $result = app(BankFeedService::class)->pull($endpoint->fresh());

        $this->assertSame(1, $result['imported']);
        $this->assertNotNull($endpoint->fresh()->last_synced_at);
        $this->assertSame('Auto feed line', BankStatementLine::query()->where('bank_account_id', $account->id)->value('narration'));
    }

    public function test_neft_csv_export_and_optional_api_push(): void
    {
        $company = $this->makeCompany();
        $payment = app(ElectronicPaymentService::class)->save($company, [
            'channel' => 'neft',
            'payment_date' => '2025-04-21',
            'amount' => '1500.00',
            'beneficiary' => 'Acme Supplies',
            'beneficiary_account' => '1234567890',
            'beneficiary_ifsc' => 'HDFC0001234',
            'status' => 'initiated',
        ]);

        Http::fake([
            'https://bank.example/upload' => Http::response('ok', 200),
        ]);

        IntegrationEndpoint::query()->create([
            'company_id' => $company->id,
            'name' => 'Payment API',
            'kind' => 'payment_api',
            'url' => 'https://bank.example/upload',
            'is_active' => true,
        ]);

        $built = app(NeftRtgsPaymentFileService::class)->exportAndMark($company, [$payment->fresh()], true);

        $this->assertStringContainsString('Acme Supplies', $built['csv']);
        $this->assertStringContainsString('HDFC0001234', $built['csv']);
        $this->assertTrue($built['pushed']);
        $this->assertSame('sent', $payment->fresh()->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://bank.example/upload');
    }

    private function makeBankAccount($company, string $name, string $number, string $ifsc): BankAccount
    {
        $bankGroup = $company->accountGroups()->where('code', 'BANK')->first();
        $this->assertNotNull($bankGroup);
        $ledger = $company->ledgers()->where('account_group_id', $bankGroup->id)->first()
            ?? \Tally\Models\Ledger::query()->create([
                'company_id' => $company->id,
                'account_group_id' => $bankGroup->id,
                'name' => $name.' Ledger',
                'opening_balance' => '0.00',
                'opening_balance_type' => 'debit',
                'is_active' => true,
            ]);

        return BankAccount::query()->create([
            'company_id' => $company->id,
            'ledger_id' => $ledger->id,
            'bank_name' => $name,
            'account_number' => $number,
            'ifsc' => $ifsc,
        ]);
    }
}
