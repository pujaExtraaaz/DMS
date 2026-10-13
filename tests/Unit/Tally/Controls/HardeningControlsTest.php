<?php

namespace Tests\Unit\Tally\Controls;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Controls\ApprovalService;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceService;
use Tally\Models\ApprovalPolicy;
use Tally\Models\ApprovalRequest;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\PaymentBatch;
use Tally\Models\Voucher;
use Tally\Parties\PartyService;
use Tally\Payables\PaymentBatchService;

class HardeningControlsTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_voucher_post_without_policy_succeeds(): void
    {
        [$company, $branch, $year, $user, $debit, $credit] = $this->journalSetup();

        $voucher = app(VoucherEngine::class)->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $year->start_date->toDateString(),
                'narration' => 'No policy',
                'entries' => [
                    ['ledger_id' => $debit->id, 'debit' => '250.00', 'credit' => '0.00'],
                    ['ledger_id' => $credit->id, 'debit' => '0.00', 'credit' => '250.00'],
                ],
            ],
            true,
        );

        $this->assertTrue($voucher->isPosted());
        $this->assertSame('none', $voucher->approval_status ?? 'none');
    }

    public function test_voucher_post_requires_approval_when_policy_matches(): void
    {
        [$company, $branch, $year, $user, $debit, $credit] = $this->journalSetup();

        ApprovalPolicy::query()->create([
            'company_id' => $company->id,
            'name' => 'Voucher over 100',
            'document_type' => 'voucher',
            'min_amount' => '100.00',
            'max_amount' => null,
            'levels' => 1,
            'is_active' => true,
        ]);

        try {
            app(VoucherEngine::class)->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $year->start_date->toDateString(),
                    'narration' => 'Needs approval',
                    'entries' => [
                        ['ledger_id' => $debit->id, 'debit' => '500.00', 'credit' => '0.00'],
                        ['ledger_id' => $credit->id, 'debit' => '0.00', 'credit' => '500.00'],
                    ],
                ],
                true,
            );
            $this->fail('Expected ValidationException for awaiting approval.');
        } catch (ValidationException $e) {
            $this->assertSame('Awaiting approval', $e->errors()['status'][0] ?? null);
        }

        $voucher = Voucher::query()->where('company_id', $company->id)->where('narration', 'Needs approval')->first();
        $this->assertNotNull($voucher);
        $this->assertTrue($voucher->isDraft());
        $this->assertSame('pending', $voucher->approval_status);

        $request = ApprovalRequest::query()
            ->where('document_type', 'voucher')
            ->where('document_id', $voucher->id)
            ->where('status', 'pending')
            ->first();
        $this->assertNotNull($request);

        app(ApprovalService::class)->approve($request, $user);
        $voucher->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $posted = app(VoucherEngine::class)->post($voucher->fresh());
        $this->assertTrue($posted->isPosted());
    }

    public function test_payment_batch_approve_closes_pending_approval_request(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->create();
        $service = app(PaymentBatchService::class);

        $batch = $service->save($company, [
            'batch_date' => now()->toDateString(),
            'status' => 'draft',
            'lines' => [],
        ]);
        $service->submit($company, $batch);

        ApprovalRequest::query()->create([
            'company_id' => $company->id,
            'document_type' => 'payment_batch',
            'document_id' => $batch->id,
            'amount' => '0.00',
            'current_level' => 1,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        $approved = $service->approve($company, $batch->fresh(), $user);
        $this->assertSame('approved', $approved->status);

        $this->assertFalse(
            ApprovalRequest::query()
                ->where('document_type', 'payment_batch')
                ->where('document_id', $batch->id)
                ->where('status', 'pending')
                ->exists()
        );
        $this->assertTrue(
            ApprovalRequest::query()
                ->where('document_type', 'payment_batch')
                ->where('document_id', $batch->id)
                ->where('status', 'approved')
                ->exists()
        );
        $this->assertInstanceOf(PaymentBatch::class, $approved);
    }

    public function test_purchase_invoice_post_blocks_duplicate_bill_unless_forced(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $party = app(PartyService::class)->save($company, [
            'type' => 'supplier',
            'name' => 'Dup Vendor 2',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $purchaseLedger = $company->ledgers()
            ->whereHas('accountGroup', fn ($q) => $q->where('code', 'PURCHASE'))
            ->first();

        if (! $purchaseLedger) {
            $group = $company->accountGroups()->where('code', 'PURCHASE')->first();
            $purchaseLedger = Ledger::query()->create([
                'company_id' => $company->id,
                'account_group_id' => $group->id,
                'name' => 'Purchase A/c',
                'opening_balance' => '0.00',
                'opening_balance_type' => 'debit',
                'is_active' => true,
            ]);
        }

        Invoice::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $year->id,
            'kind' => InvoiceKind::Purchase,
            'invoice_number' => 'PINV-DUP-1',
            'invoice_date' => $year->start_date->toDateString(),
            'party_ledger_id' => $party->ledger_id,
            'account_ledger_id' => $purchaseLedger->id,
            'reference_number' => 'BILL-42',
            'status' => VoucherStatus::Posted,
            'subtotal' => '100.00',
            'discount_total' => '0.00',
            'tax_total' => '0.00',
            'grand_total' => '100.00',
            'created_by' => $user->id,
        ]);

        $draft = app(InvoiceService::class)->save(
            $company,
            $branch,
            $year,
            $user,
            InvoiceKind::Purchase,
            [
                'invoice_date' => $year->start_date->toDateString(),
                'party_ledger_id' => $party->ledger_id,
                'account_ledger_id' => $purchaseLedger->id,
                'reference_number' => 'BILL-42',
                'lines' => [
                    ['item_name' => 'Widget', 'quantity' => '1', 'rate' => '100', 'discount' => '0', 'tax_amount' => '0'],
                ],
            ],
            false,
        );

        try {
            app(InvoiceService::class)->post($draft, $user);
            $this->fail('Expected duplicate bill ValidationException.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('PINV-DUP-1', $e->errors()['reference_number'][0] ?? '');
            $this->assertStringContainsString('force_duplicate', $e->errors()['reference_number'][0] ?? '');
        }

        $posted = app(InvoiceService::class)->post($draft->fresh(), $user, true);
        $this->assertTrue($posted->isPosted());
    }

    /**
     * @return array{0: \Tally\Models\Company, 1: \Tally\Models\Branch, 2: \Tally\Models\FinancialYear, 3: User, 4: Ledger, 5: Ledger}
     */
    private function journalSetup(): array
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $expenses = $company->accountGroups()->where('code', 'INDIRECT_EXPENSES')->first();
        $liabilities = $company->accountGroups()->where('code', 'CURRENT_LIABILITIES')->first();

        $debit = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $expenses->id,
            'name' => 'Misc Expense',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $credit = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $liabilities->id,
            'name' => 'Misc Payable',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        return [$company, $branch, $year, $user, $debit, $credit];
    }
}
