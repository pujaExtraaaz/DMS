<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyClassificationAndGemTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_can_be_saved_without_classification_and_gem_fields_stay_hidden_when_not_registered(): void
    {
        $user = $this->partyUser();
        CustomerType::create(['name' => 'Retail', 'code' => 'RET', 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('masters.customers.store'), [
            'name' => 'Plain Party',
            'party_type' => 'sundry_debtors',
            'customer_type_id' => '',
            'email' => 'plain@example.com',
            'udyam_number' => 'UDYAM-PB-12-0001',
            'website' => 'https://plain.example',
            'location_link' => 'https://maps.example/plain',
        ]);

        $response->assertRedirect(route('masters.customers.index'));

        $party = Customer::query()->where('name', 'Plain Party')->firstOrFail();
        $this->assertNull($party->customer_type_id);
        $this->assertSame('UDYAM-PB-12-0001', $party->udyam_number);
        $this->assertCount(0, $party->gemAuthorizations);
        $this->assertSame('https://plain.example', $party->website);
        $this->assertSame('https://maps.example/plain', $party->location_link);
    }

    public function test_a_party_can_store_several_oem_gem_authorizations_with_documents(): void
    {
        Storage::fake('public');
        $user = $this->partyUser();
        $sony = $this->brand($user->company_id, 'Sony');
        $epson = $this->brand($user->company_id, 'Epson');
        $dell = $this->brand($user->company_id, 'Dell');

        $missing = $this->actingAs($user)->from(route('masters.customers.create'))->post(route('masters.customers.store'), [
            'name' => 'Gem Party',
            'party_type' => 'sundry_debtors',
            'gem_authorizations' => [
                ['brand_id' => $sony->id, 'gem_registered' => '1', 'gem_authorized' => '1', 'authorization_number' => ''],
            ],
        ]);
        $missing->assertRedirect(route('masters.customers.create'));
        $missing->assertSessionHasErrors(['gem_authorizations.0.authorization_number']);

        $saved = $this->actingAs($user)->post(route('masters.customers.store'), [
            'name' => 'Gem Party',
            'party_type' => 'sundry_debtors',
            'gem_authorizations' => [
                [
                    'brand_id' => $sony->id,
                    'gem_registered' => '1',
                    'gem_authorized' => '1',
                    'authorization_number' => 'GEM-SONY-100',
                    'documents' => [
                        UploadedFile::fake()->create('sony-star.pdf', 20, 'application/pdf'),
                    ],
                ],
                [
                    'brand_id' => $epson->id,
                    'gem_registered' => '0',
                    'gem_authorized' => '1',
                    'authorization_number' => 'SHOULD-NOT-SAVE',
                ],
                [
                    'brand_id' => $dell->id,
                    'gem_registered' => '1',
                    'gem_authorized' => '0',
                    'authorization_number' => '',
                ],
            ],
        ]);

        $saved->assertRedirect(route('masters.customers.index'));
        $party = Customer::query()->where('name', 'Gem Party')->firstOrFail();
        $rows = $party->gemAuthorizations()->with('documents')->orderBy('id')->get();
        $this->assertCount(3, $rows);

        $sonyRow = $rows->firstWhere('brand_id', $sony->id);
        $this->assertTrue($sonyRow->gem_registered);
        $this->assertTrue($sonyRow->gem_authorized);
        $this->assertSame('GEM-SONY-100', $sonyRow->authorization_number);
        $this->assertCount(1, $sonyRow->documents);
        Storage::disk('public')->assertExists($sonyRow->documents->first()->path);

        $epsonRow = $rows->firstWhere('brand_id', $epson->id);
        $this->assertFalse($epsonRow->gem_registered);
        $this->assertFalse($epsonRow->gem_authorized);
        $this->assertNull($epsonRow->authorization_number);
        $this->assertCount(0, $epsonRow->documents);

        $dellRow = $rows->firstWhere('brand_id', $dell->id);
        $this->assertTrue($dellRow->gem_registered);
        $this->assertFalse($dellRow->gem_authorized);
        $this->assertNull($dellRow->authorization_number);
    }

    public function test_classification_can_be_edited_and_deleted_from_the_party_form(): void
    {
        $user = $this->partyUser();
        $type = CustomerType::create(['name' => 'Oem', 'code' => 'OEM', 'is_active' => true]);
        $party = Customer::create([
            'company_id' => $user->company_id,
            'name' => 'Classified Party',
            'code' => 'PTY-CLS-1',
            'customer_type_id' => $type->id,
            'party_type' => 'sundry_debtors',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->putJson(route('masters.customer-types.quick-update', $type), ['name' => 'Corporate Oem'])
            ->assertOk()
            ->assertJsonPath('item.name', 'Corporate Oem');

        $this->assertSame('Corporate Oem', $type->fresh()->name);

        $this->actingAs($user)
            ->deleteJson(route('masters.customer-types.quick-destroy', $type))
            ->assertOk();

        $this->assertNull(CustomerType::query()->find($type->id));
        $this->assertNull($party->fresh()->customer_type_id);
    }

    private function partyUser(): User
    {
        $company = Company::create([
            'name' => 'Party Test Co',
            'code' => 'PTC',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        return $user;
    }

    private function brand(int $companyId, string $name): Brand
    {
        $brand = new Brand;
        $brand->forceFill([
            'company_id' => $companyId,
            'name' => $name,
            'is_active' => true,
        ])->save();

        return $brand;
    }
}
