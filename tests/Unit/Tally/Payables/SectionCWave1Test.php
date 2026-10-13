<?php

namespace Tests\Unit\Tally\Payables;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\VoucherStatus;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Invoice;
use Tally\Parties\PartyService;
use Tally\Payables\DuplicateBillDetector;
use Tally\Purchasing\PurchaseRequisitionService;
use Tally\Receivables\AgeingReport;

class SectionCWave1Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_purchase_requisition_saves_with_number_and_lines(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $pr = app(PurchaseRequisitionService::class)->save($company, $branch, $year, $user, [
            'requisition_date' => $year->start_date->toDateString(),
            'priority' => 'high',
            'status' => 'draft',
            'lines' => [
                ['item_name' => 'Raw material', 'quantity' => '10', 'unit' => 'Kg'],
                ['item_name' => 'Packaging', 'quantity' => '5', 'unit' => 'Nos'],
            ],
        ]);

        $this->assertSame('PR-000001', $pr->number);
        $this->assertSame('high', $pr->priority);
        $this->assertCount(2, $pr->lines);
        $this->assertSame('Raw material', $pr->lines->first()->item_name);
    }

    public function test_payables_ageing_method_returns_structure(): void
    {
        $company = $this->makeCompany();
        $report = app(AgeingReport::class)->payables($company, now()->toDateString());

        $this->assertArrayHasKey('rows', $report);
        $this->assertArrayHasKey('totals', $report);
        $this->assertArrayHasKey('current', $report['totals']);
        $this->assertArrayHasKey('b1_30', $report['totals']);
        $this->assertArrayHasKey('b31_60', $report['totals']);
        $this->assertArrayHasKey('b61_90', $report['totals']);
        $this->assertArrayHasKey('b90_plus', $report['totals']);
        $this->assertArrayHasKey('total', $report['totals']);
        $this->assertIsArray($report['rows']);
    }

    public function test_duplicate_bill_detector_flags_matching_purchase_invoice(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();

        $party = app(PartyService::class)->save($company, [
            'type' => 'supplier',
            'name' => 'Dup Vendor',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $purchaseLedger = $company->ledgers()
            ->whereHas('accountGroup', fn ($q) => $q->where('code', 'PURCHASE'))
            ->first() ?? $party->ledger;

        Invoice::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $year->id,
            'kind' => InvoiceKind::Purchase,
            'invoice_number' => 'PINV-001',
            'invoice_date' => $year->start_date->toDateString(),
            'party_ledger_id' => $party->ledger_id,
            'account_ledger_id' => $purchaseLedger->id,
            'reference_number' => 'SUP-BILL-99',
            'status' => VoucherStatus::Posted,
            'subtotal' => '1000.00',
            'discount_total' => '0.00',
            'tax_total' => '180.00',
            'grand_total' => '1180.00',
        ]);

        $detector = app(DuplicateBillDetector::class);

        $this->assertTrue($detector->isDuplicate($company, (int) $party->ledger_id, 'SUP-BILL-99', '1180.00'));
        $this->assertFalse($detector->isDuplicate($company, (int) $party->ledger_id, 'SUP-BILL-99', '999.00'));

        $flags = $detector->flag($company, (int) $party->ledger_id, 'SUP-BILL-99', '1180.00');
        $this->assertCount(1, $flags);
        $this->assertSame('flagged', $flags[0]->status);
        $this->assertSame('SUP-BILL-99', $flags[0]->bill_number);
    }
}
