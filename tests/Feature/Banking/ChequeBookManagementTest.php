<?php

namespace Tests\Feature\Banking;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\ChequeBook;
use App\Domains\Organization\Models\Company;
use App\Domains\Payment\Models\Cheque;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChequeBookManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->company = Company::create([
            'name' => 'Alpha Corporation',
            'code' => 'ALPHA-01',
            'is_active' => true,
            'is_default' => true,
            'bank_accounts' => [
                [
                    'bank_name' => 'HDFC Bank',
                    'bank_account_no' => '50200012345678',
                    'bank_ifsc' => 'HDFC0001234',
                    'branch' => 'Main Branch',
                ],
                [
                    'bank_name' => 'State Bank of India',
                    'bank_account_no' => '102030405060',
                    'bank_ifsc' => 'SBIN0001234',
                    'branch' => 'City Branch',
                ],
            ],
        ]);

        $this->user->update(['company_id' => $this->company->id]);
    }

    public function test_can_view_cheque_books_management_screen(): void
    {
        ChequeBook::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'bank_name' => 'HDFC Bank',
            'cheque_book_name' => 'HDFC Primary',
            'from_number' => '000001',
            'to_number' => '000025',
            'from_numeric' => 1,
            'to_numeric' => 25,
            'total_cheques' => 25,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('cheque-books.index'));

        $response->assertOk();
        $response->assertSee('Cheque Book Management');
        $response->assertSee('HDFC Primary');
        $response->assertSee('000001');
        $response->assertSee('000025');
        $response->assertSee('50200012345678');
    }

    public function test_can_store_new_cheque_book(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'bank_name' => 'HDFC Bank',
            'cheque_book_name' => 'HDFC Operational',
            'from_number' => '000101',
            'to_number' => '000150',
            'issue_date' => now()->toDateString(),
            'status' => 'active',
            'remarks' => 'Office vendor disbursements',
        ];

        $response = $this->actingAs($this->user)->post(route('organization.cheque-books.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('cheque_books', [
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'cheque_book_name' => 'Hdfc Operational',
            'from_number' => '000101',
            'to_number' => '000150',
            'from_numeric' => 101,
            'to_numeric' => 150,
            'total_cheques' => 50,
            'status' => 'active',
        ]);
    }

    public function test_blocks_invalid_cheque_book_ranges(): void
    {
        // Ending number less than starting number
        $response = $this->actingAs($this->user)
            ->from(route('cheque-books.index'))
            ->post(route('organization.cheque-books.store'), [
                'company_id' => $this->company->id,
                'bank_account_no' => '50200012345678',
                'from_number' => '000050',
                'to_number' => '000020',
            ]);

        $response->assertSessionHasErrors(['to_number']);

        // Non-numeric starting number
        $response2 = $this->actingAs($this->user)
            ->from(route('cheque-books.index'))
            ->post(route('organization.cheque-books.store'), [
                'company_id' => $this->company->id,
                'bank_account_no' => '50200012345678',
                'from_number' => 'CHQ001',
                'to_number' => '000050',
            ]);

        $response2->assertSessionHasErrors(['from_number']);
    }

    public function test_blocks_overlapping_cheque_ranges_for_same_bank_account(): void
    {
        ChequeBook::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'from_number' => '000100',
            'to_number' => '000200',
            'from_numeric' => 100,
            'to_numeric' => 200,
            'total_cheques' => 101,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Attempt overlapping range (150 - 250)
        $response = $this->actingAs($this->user)
            ->from(route('cheque-books.index'))
            ->post(route('organization.cheque-books.store'), [
                'company_id' => $this->company->id,
                'bank_account_no' => '50200012345678',
                'from_number' => '000150',
                'to_number' => '000250',
            ]);

        $response->assertSessionHasErrors(['from_number']);
    }

    public function test_can_toggle_cheque_book_status(): void
    {
        $book = ChequeBook::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'from_number' => '000001',
            'to_number' => '000025',
            'from_numeric' => 1,
            'to_numeric' => 25,
            'total_cheques' => 25,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)->post(route('organization.cheque-books.toggle-status', $book));
        $this->assertEquals('inactive', $book->fresh()->status);

        $this->actingAs($this->user)->post(route('organization.cheque-books.toggle-status', $book));
        $this->assertEquals('active', $book->fresh()->status);
    }

    public function test_can_void_individual_unused_cheque_leaf(): void
    {
        $book = ChequeBook::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'from_number' => '000001',
            'to_number' => '000010',
            'from_numeric' => 1,
            'to_numeric' => 10,
            'total_cheques' => 10,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('organization.cheque-books.cancel-leaf', $book), [
            'cheque_number' => '000005',
            'reason' => 'Leaf was spoiled during printer jam',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cheques', [
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'cheque_book_id' => $book->id,
            'cheque_no' => '000005',
            'status' => 'cancelled',
            'cancelled_reason' => 'Leaf Was Spoiled During Printer Jam',
        ]);

        $this->assertTrue($book->isNumberCancelled('000005'));
        $this->assertEquals(1, $book->cancelled_count);
        $this->assertEquals(9, $book->remaining_count);
    }

    public function test_available_numbers_api_returns_suggestions(): void
    {
        $book = ChequeBook::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'from_number' => '000001',
            'to_number' => '000005',
            'from_numeric' => 1,
            'to_numeric' => 5,
            'total_cheques' => 5,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        // Mark 000001 as used
        Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'cheque_book_id' => $book->id,
            'cheque_no' => '000001',
            'cheque_type' => 'issued',
            'direction' => 'issued_to_vendor',
            'amount' => 5000,
            'status' => 'issued',
            'recorded_by' => $this->user->id,
        ]);

        // Void 000002
        $book->cancelLeaf('000002', 'Damaged', $this->user->id);

        $response = $this->actingAs($this->user)->getJson(route('cheque-books.available-numbers', [
            'bank_account_no' => '50200012345678',
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'suggested_cheque_no' => '000003',
        ]);

        $avail = $response->json('books.0.available_numbers');
        $this->assertEquals(['000003', '000004', '000005'], $avail);
        $this->assertEquals(3, $response->json('books.0.remaining_count'));
    }

    public function test_blocks_deleting_cheque_book_with_used_leaves(): void
    {
        $book = ChequeBook::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'from_number' => '000001',
            'to_number' => '000025',
            'from_numeric' => 1,
            'to_numeric' => 25,
            'total_cheques' => 25,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50200012345678',
            'cheque_book_id' => $book->id,
            'cheque_no' => '000001',
            'cheque_type' => 'issued',
            'direction' => 'issued_to_vendor',
            'amount' => 1200,
            'status' => 'issued',
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('organization.cheque-books.destroy', $book));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('cheque_books', ['id' => $book->id]);
    }
}
