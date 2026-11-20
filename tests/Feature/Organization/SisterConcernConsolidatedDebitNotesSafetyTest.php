<?php

namespace Tests\Feature\Organization;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Services\ConsolidatedReportService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SisterConcernConsolidatedDebitNotesSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Safety Test Company',
            'code' => 'STC',
            'is_active' => true,
        ]);

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole('super-admin');
    }

    public function test_compute_purchases_summary_handles_supplier_payables_without_amount_column(): void
    {
        // Insert a record into supplier_payables with debit_note type if table exists
        if (Schema::hasTable('supplier_payables')) {
            $supplier = Customer::create([
                'company_id' => $this->company->id,
                'name' => 'Supplier Co',
                'code' => 'SUPP-01',
                'type' => 'supplier',
                'is_active' => true,
            ]);

            DB::table('supplier_payables')->insert([
                'supplier_id' => $supplier->id,
                'type' => 'debit_note',
                'debit' => 1500.00,
                'credit' => 0.00,
                'balance' => 1500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $service = app(ConsolidatedReportService::class);
        $fromDate = now()->startOfYear()->toDateString();
        $toDate = now()->endOfYear()->toDateString();

        // Must execute cleanly without SQLSTATE[42S22] unknown column 'amount' exception
        $summary = $service->computePurchasesSummary([$this->company->id], $fromDate, $toDate);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('purchase_returns', $summary);
        $this->assertArrayHasKey('debit_notes_total', $summary);
        $this->assertGreaterThanOrEqual(0, $summary['purchase_returns']);
    }

    public function test_sister_concerns_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('organization.sister-concerns.dashboard'));

        $response->assertStatus(200);
    }
}
