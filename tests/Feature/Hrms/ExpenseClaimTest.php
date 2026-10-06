<?php

namespace Tests\Feature\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\ExpenseClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpenseClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        foreach (['hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage']);
        return $user;
    }

    public function test_expense_claim_submission_with_receipt(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-401', 'name' => 'Vikram Roy', 'status' => 'active']);

        $receipt = UploadedFile::fake()->create('hotel_bill.pdf', 300, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('hrms.expense-claims.store'), [
            'employee_id' => $employee->id,
            'claim_date' => '2026-10-02',
            'claim_type' => 'Hotel Lodging',
            'amount' => 4500.50,
            'description' => 'Client meeting hotel stay',
            'receipt_file' => $receipt,
        ]);

        $response->assertRedirect(route('hrms.expense-claims.index'));

        $claim = ExpenseClaim::first();
        $this->assertNotNull($claim);
        $this->assertEquals(4500.50, (float) $claim->amount);
        $this->assertEquals('pending', $claim->status);
        $this->assertNotNull($claim->receipt_path);
        Storage::disk('local')->assertExists($claim->receipt_path);
    }

    public function test_expense_claim_approval_and_settlement_workflow(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-402', 'name' => 'Alok Nath', 'status' => 'active']);

        $claim = ExpenseClaim::create([
            'employee_id' => $employee->id,
            'claim_date' => '2026-10-03',
            'claim_type' => 'Travel Fuel',
            'amount' => 1200.00,
            'status' => 'pending',
        ]);

        // Attempting to settle a pending claim should fail
        $settleFail = $this->actingAs($admin)->post(route('hrms.expense-claims.settle', $claim), [
            'settlement_amount' => 1200.00,
        ]);
        $settleFail->assertSessionHas('error');

        // Approve claim
        $approveResponse = $this->actingAs($admin)->post(route('hrms.expense-claims.approve', $claim));
        $approveResponse->assertRedirect();
        $claim->refresh();
        $this->assertEquals('approved', $claim->status);

        // Now settle claim
        $settleResponse = $this->actingAs($admin)->post(route('hrms.expense-claims.settle', $claim), [
            'settlement_amount' => 1200.00,
            'settlement_reference' => 'NEFT-889900',
            'settlement_notes' => 'Paid via corporate account',
        ]);

        $settleResponse->assertRedirect();
        $claim->refresh();
        $this->assertEquals('settled', $claim->status);
        $this->assertEquals(1200.00, (float) $claim->settlement_amount);
        $this->assertEquals('NEFT-889900', $claim->settlement_reference);
        $this->assertEquals($admin->id, $claim->settled_by);
        $this->assertNotNull($claim->settled_at);
    }
}

