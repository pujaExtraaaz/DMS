<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Area $mumbaiArea;
    protected Area $puneArea;
    protected CustomerType $dealerType;
    protected CustomerType $retailerType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::firstOrCreate(
            ['code' => 'CMP-AC-TEST'],
            ['name' => 'Autocomplete Corp', 'is_active' => true]
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR-AC-TEST'],
            ['company_id' => $this->company->id, 'name' => 'Autocomplete Branch', 'is_active' => true]
        );

        $this->user = User::firstOrCreate(
            ['email' => 'ac-admin@dms.test'],
            [
                'name' => 'Autocomplete Admin',
                'password' => bcrypt('password'),
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
            ]
        );
        $this->user->assignRole($role);

        $this->mumbaiArea = Area::firstOrCreate(['name' => 'Mumbai Suburban'], ['code' => 'AREA-MUM', 'is_active' => true]);
        $this->puneArea = Area::firstOrCreate(['name' => 'Pune Central'], ['code' => 'AREA-PUN', 'is_active' => true]);

        $this->dealerType = CustomerType::firstOrCreate(['name' => 'Dealer', 'code' => 'DLR'], ['is_active' => true]);
        $this->retailerType = CustomerType::firstOrCreate(['name' => 'Retailer', 'code' => 'RTL'], ['is_active' => true]);
    }

    public function test_autocomplete_returns_available_parties_when_query_is_empty_on_click_focus(): void
    {
        Customer::create([
            'name' => 'Initial Click Party A',
            'code' => 'PTY-INIT-01',
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Initial Click Party B',
            'code' => 'PTY-INIT-02',
            'is_active' => true,
        ]);

        // When user clicks the search input without typing, it returns available parties
        $response = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete'));
        $response->assertOk();
        $response->assertJsonCount(2);

        $responseEmptyQ = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => '']));
        $responseEmptyQ->assertOk();
        $responseEmptyQ->assertJsonCount(2);
    }

    public function test_autocomplete_searches_by_area_name_and_code(): void
    {
        Customer::create([
            'name' => 'Kalyan Enterprises',
            'code' => 'PTY-KALYAN-01',
            'area_id' => $this->mumbaiArea->id,
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Shivaji Logistics',
            'code' => 'PTY-SHIV-01',
            'area_id' => $this->puneArea->id,
            'is_active' => true,
        ]);

        // Search by area name "Mumbai"
        $resAreaName = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => 'Mumbai']));
        $resAreaName->assertOk();
        $resAreaName->assertJsonCount(1);
        $resAreaName->assertJsonPath('0.name', 'Kalyan Enterprises');
        $resAreaName->assertJsonPath('0.area_name', 'Mumbai Suburban');

        // Search by area code "AREA-PUN"
        $resAreaCode = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => 'AREA-PUN']));
        $resAreaCode->assertOk();
        $resAreaCode->assertJsonCount(1);
        $resAreaCode->assertJsonPath('0.name', 'Shivaji Logistics');
        $resAreaCode->assertJsonPath('0.area_name', 'Pune Central');
    }

    public function test_autocomplete_searches_by_full_and_partial_party_name(): void
    {
        Customer::create([
            'name' => 'Apex Technologies Pvt Ltd',
            'code' => 'PTY-APEX-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'phone' => '9820011223',
            'gstin' => '27AAACA1234A1Z5',
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Bharat Dynamics',
            'code' => 'PTY-BHARAT-002',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'phone' => '9820099887',
            'is_active' => true,
        ]);

        // Partial match lowercase
        $res1 = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => 'apex']));
        $res1->assertOk();
        $res1->assertJsonCount(1);
        $res1->assertJsonPath('0.name', 'Apex Technologies Pvt Ltd');
        $res1->assertJsonPath('0.code', 'PTY-APEX-001');

        // Full match
        $res2 = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => 'Bharat Dynamics']));
        $res2->assertOk();
        $res2->assertJsonCount(1);
        $res2->assertJsonPath('0.name', 'Bharat Dynamics');
    }

    public function test_autocomplete_searches_by_phone_number(): void
    {
        Customer::create([
            'name' => 'Coastal Logistics',
            'code' => 'PTY-COAST-003',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => '987654']));
        $res->assertOk();
        $res->assertJsonCount(1);
        $res->assertJsonPath('0.name', 'Coastal Logistics');
        $res->assertJsonPath('0.phone', '9876543210');
    }

    public function test_autocomplete_searches_by_gstin(): void
    {
        Customer::create([
            'name' => 'Delta Steel Infra',
            'code' => 'PTY-DELTA-004',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'gstin' => '27ABCDE1234F1Z8',
            'phone' => '9123456789',
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => '27ABCDE']));
        $res->assertOk();
        $res->assertJsonCount(1);
        $res->assertJsonPath('0.name', 'Delta Steel Infra');
        $res->assertJsonPath('0.gstin', '27ABCDE1234F1Z8');
    }

    public function test_autocomplete_returns_all_expected_fields_and_urls(): void
    {
        $party = Customer::create([
            'name' => 'Elite Retailers Hub',
            'code' => 'PTY-ELITE-005',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'phone' => '9988776655',
            'email' => 'contact@eliteretail.test',
            'gstin' => '27AABCE5555D1Z2',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'area_id' => $this->mumbaiArea->id,
            'customer_type_id' => $this->dealerType->id,
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => 'Elite']));
        $res->assertOk();
        $res->assertJsonCount(1);

        $item = $res->json('0');
        $this->assertSame($party->id, $item['id']);
        $this->assertSame('Elite Retailers Hub', $item['name']);
        $this->assertSame('PTY-ELITE-005', $item['code']);
        $this->assertSame('Sundry Debtors', $item['party_type_label']);
        $this->assertSame('sundry_debtors', $item['party_type_key']);
        $this->assertSame('9988776655', $item['phone']);
        $this->assertSame('contact@eliteretail.test', $item['email']);
        $this->assertSame('27AABCE5555D1Z2', $item['gstin']);
        $this->assertSame('Maharashtra', $item['state']);
        $this->assertSame('Mumbai Suburban', $item['area_name']);
        $this->assertSame('Dealer', $item['category_name']);
        $this->assertTrue($item['is_active']);
        $this->assertSame(route('masters.customers.show', $party), $item['view_url']);
        $this->assertSame(route('masters.customers.edit', $party), $item['edit_url']);
    }

    public function test_autocomplete_respects_party_type_filter(): void
    {
        Customer::create([
            'name' => 'Future Debtor Store',
            'code' => 'PTY-FUT-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'phone' => '9111111111',
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Future Creditor Mills',
            'code' => 'PTY-FUT-002',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'phone' => '9222222222',
            'is_active' => true,
        ]);

        // Search "Future" without filter -> 2 results
        $resAll = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => 'Future']));
        $resAll->assertJsonCount(2);

        // Search "Future" with party_type = sundry_debtors -> 1 result
        $resDebtors = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', [
            'q' => 'Future',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
        ]));
        $resDebtors->assertJsonCount(1);
        $resDebtors->assertJsonPath('0.name', 'Future Debtor Store');

        // Search "Future" with party_type = sundry_creditors -> 1 result
        $resCreditors = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', [
            'q' => 'Future',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
        ]));
        $resCreditors->assertJsonCount(1);
        $resCreditors->assertJsonPath('0.name', 'Future Creditor Mills');
    }

    public function test_autocomplete_respects_area_and_category_filters(): void
    {
        Customer::create([
            'name' => 'Galaxy Mumbai Dealer',
            'code' => 'PTY-GAL-001',
            'area_id' => $this->mumbaiArea->id,
            'customer_type_id' => $this->dealerType->id,
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Galaxy Pune Retailer',
            'code' => 'PTY-GAL-002',
            'area_id' => $this->puneArea->id,
            'customer_type_id' => $this->retailerType->id,
            'is_active' => true,
        ]);

        // Area filter
        $resArea = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', [
            'q' => 'Galaxy',
            'area_id' => $this->mumbaiArea->id,
        ]));
        $resArea->assertJsonCount(1);
        $resArea->assertJsonPath('0.name', 'Galaxy Mumbai Dealer');

        // Category filter
        $resCat = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', [
            'q' => 'Galaxy',
            'customer_type_id' => $this->retailerType->id,
        ]));
        $resCat->assertJsonCount(1);
        $resCat->assertJsonPath('0.name', 'Galaxy Pune Retailer');
    }

    public function test_autocomplete_respects_status_filter(): void
    {
        Customer::create([
            'name' => 'Horizon Active Party',
            'code' => 'PTY-HOR-001',
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Horizon Inactive Party',
            'code' => 'PTY-HOR-002',
            'is_active' => false,
        ]);

        // Active only
        $resActive = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', [
            'q' => 'Horizon',
            'status' => 'active',
        ]));
        $resActive->assertJsonCount(1);
        $resActive->assertJsonPath('0.name', 'Horizon Active Party');

        // Inactive only
        $resInactive = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', [
            'q' => 'Horizon',
            'status' => 'inactive',
        ]));
        $resInactive->assertJsonCount(1);
        $resInactive->assertJsonPath('0.name', 'Horizon Inactive Party');
    }

    public function test_autocomplete_returns_empty_when_no_records_match(): void
    {
        Customer::create([
            'name' => 'Zenith Traders',
            'code' => 'PTY-ZEN-001',
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => 'NonExistentPartyNameXYZ']));
        $res->assertOk();
        $res->assertExactJson([]);
    }

    public function test_autocomplete_limits_results_to_ten_by_default(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Customer::create([
                'name' => sprintf('Standard Bulk Item %02d', $i),
                'code' => sprintf('PTY-BULK-%02d', $i),
                'is_active' => true,
            ]);
        }

        $res = $this->actingAs($this->user)->getJson(route('masters.parties.autocomplete', ['q' => 'Standard Bulk']));
        $res->assertOk();
        $res->assertJsonCount(10);
    }

    public function test_parties_listing_page_renders_autocomplete_url_and_markup(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.parties.index'));
        $response->assertOk();

        // Check that the table toolbar received the autocomplete-url prop
        $response->assertSee(route('masters.parties.autocomplete'));
        $response->assertSee('dmsTableToolbarSearch');
        $response->assertSee('Searching matching parties...');
        $response->assertSee('No matching parties found');
    }

    public function test_parties_listing_filters_strictly_by_party_id(): void
    {
        $partyA = Customer::create([
            'name' => 'Target Alpha Enterprises',
            'code' => 'PTY-ALPHA-001',
            'phone' => '9870001111',
            'area_id' => $this->mumbaiArea->id,
            'is_active' => true,
        ]);

        $partyB = Customer::create([
            'name' => 'Target Beta Logistics',
            'code' => 'PTY-BETA-002',
            'phone' => '9870002222',
            'area_id' => $this->puneArea->id,
            'is_active' => true,
        ]);

        // Requesting with party_id filters strictly to that party
        $response = $this->actingAs($this->user)->get(route('masters.parties.index', [
            'party_id' => $partyA->id,
            'search' => 'Target Alpha Enterprises',
        ]));

        $response->assertOk();
        $response->assertSee('Target Alpha Enterprises');
        $response->assertDontSee('Target Beta Logistics');
        $response->assertSee('Filtering by Party:');
        $response->assertSee('PTY-ALPHA-001');
        $response->assertSee('Clear Party Filter');
        $response->assertSee(route('masters.customers.show', $partyA));
    }

    public function test_parties_listing_dynamic_request_with_party_id_returns_partial_view(): void
    {
        $partyA = Customer::create([
            'name' => 'Seamless Dynamic Trader',
            'code' => 'PTY-SEAM-001',
            'is_active' => true,
        ]);

        $partyB = Customer::create([
            'name' => 'Other Supplier Corp',
            'code' => 'PTY-OTH-002',
            'is_active' => true,
        ]);

        // Simulating the dynamic table swap header X-DMS-Dynamic
        $response = $this->actingAs($this->user)->withHeaders([
            'X-DMS-Dynamic' => '1',
        ])->get(route('masters.parties.index', [
            'party_id' => $partyA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Seamless Dynamic Trader');
        $response->assertDontSee('Other Supplier Corp');
        $response->assertSee('Filtering by Party:');
        $response->assertSee('Clear Party Filter');
    }
}
