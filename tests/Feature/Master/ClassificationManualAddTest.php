<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassificationManualAddTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_quick_store_creates_new_classification(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('masters.customer-types.quick-store'), [
            'name' => 'Super Stockist',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Classification created successfully.',
                'item' => [
                    'name' => 'Super Stockist',
                ],
            ]);

        $this->assertDatabaseHas('customer_types', [
            'name' => 'Super Stockist',
            'is_active' => true,
        ]);

        $type = CustomerType::where('name', 'Super Stockist')->first();
        $this->assertNotNull($type);
        $this->assertNotEmpty($type->code);
    }

    public function test_quick_store_validates_required_name(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('masters.customer-types.quick-store'), [
            'name' => '   ',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Classification Name is required.',
            ]);
    }

    public function test_quick_store_prevents_duplicate_case_insensitive_with_whitespace(): void
    {
        CustomerType::create([
            'name' => 'Institutional Buyer',
            'code' => 'INST',
            'is_active' => true,
        ]);

        // Same name with different casing and extra spaces
        $response = $this->actingAs($this->user)->postJson(route('masters.customer-types.quick-store'), [
            'name' => '  institutional buyer  ',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'A classification with this name already exists.',
            ]);
    }

    public function test_add_party_form_displays_classification_and_add_manually(): void
    {
        CustomerType::create([
            'name' => 'Premium Distributor',
            'code' => 'PREM',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check Classification label and Add Manually action
        $this->assertStringContainsString('Classification', $content);
        $this->assertStringContainsString('Add Manually', $content);
        $this->assertStringContainsString('Premium Distributor', $content);
        $this->assertStringContainsString('openClassificationModal()', $content);
        $this->assertStringContainsString('saveClassification()', $content);
    }

    public function test_party_can_be_saved_with_newly_created_classification(): void
    {
        // 1. Create classification via quick-store
        $storeResponse = $this->actingAs($this->user)->postJson(route('masters.customer-types.quick-store'), [
            'name' => 'Franchise Partner',
        ]);
        $storeResponse->assertStatus(201);
        $classificationId = $storeResponse->json('item.id');

        // 2. Create customer using this classification
        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), [
            'name' => 'Franchise Store 01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $classificationId,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('masters.customers.index'));

        $customer = Customer::where('name', 'Franchise Store 01')->firstOrFail();
        $this->assertEquals($classificationId, $customer->customer_type_id);
        $this->assertEquals('Franchise Partner', $customer->customerType->name);

        // 3. Edit party form has the classification selected
        $editResponse = $this->actingAs($this->user)->get(route('masters.customers.edit', $customer));
        $editResponse->assertStatus(200);
        $editContent = $editResponse->getContent();
        $this->assertStringContainsString('value="' . $classificationId . '" selected', $editContent);

        // 4. Subsequent new party form includes the new classification in the dropdown
        $subsequentResponse = $this->actingAs($this->user)->get(route('masters.customers.create'));
        $subsequentResponse->assertStatus(200);
        $this->assertStringContainsString('Franchise Partner', $subsequentResponse->getContent());
    }

    public function test_unauthorized_user_without_permission_is_rejected(): void
    {
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)->postJson(route('masters.customer-types.quick-store'), [
            'name' => 'Unauthorized Type',
        ]);

        $response->assertStatus(403);
    }
}

