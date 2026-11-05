<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\CustomCity;
use App\Domains\Master\Models\Route;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\RegionBrandPolicy;
use App\Models\User;
use App\Support\IndianCities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyCityAreaRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_punjab_lists_jagraon_and_gurdaspur_and_a_new_city_is_saved(): void
    {
        $cities = IndianCities::getCitiesForState('Punjab');
        $this->assertContains('Jagraon', $cities);
        $this->assertContains('Gurdaspur', $cities);
        $this->assertGreaterThan(150, count($cities));
        $this->assertContains('Achalpur', IndianCities::getCitiesForState('Maharashtra'));
        $this->assertGreaterThan(500, count(IndianCities::getCitiesForState('Maharashtra')));
        $this->assertContains('Gangtok', IndianCities::getCitiesForState('Sikkim'));
        foreach (\App\Support\IndianStates::namesAlphabetical() as $state) {
            $this->assertNotEmpty(IndianCities::getCitiesForState($state), $state);
        }

        $user = $this->partyUser();

        $this->actingAs($user)
            ->postJson(route('masters.cities.quick-store'), [
                'state' => 'Punjab',
                'name' => 'Bhoa',
            ])
            ->assertCreated()
            ->assertJsonPath('item.name', 'Bhoa');

        $this->assertTrue(CustomCity::query()->where('state', 'Punjab')->where('name', 'Bhoa')->exists());
        $this->assertContains('Bhoa', IndianCities::getCitiesForState('Punjab'));

        $this->actingAs($user)
            ->postJson(route('masters.cities.quick-store'), [
                'state' => 'Punjab',
                'name' => 'Mumbai',
            ])
            ->assertStatus(422);
    }

    public function test_area_and_delivery_route_can_be_added_edited_and_deleted_from_the_party_form(): void
    {
        $user = $this->partyUser();
        $type = CustomerType::create(['name' => 'Retail', 'code' => 'RET', 'is_active' => true]);

        $created = $this->actingAs($user)
            ->postJson(route('masters.areas.quick-store'), ['name' => 'punjab north'])
            ->assertCreated();

        $areaId = $created->json('item.id');
        $this->assertSame('Punjab North', $created->json('item.name'));

        $this->actingAs($user)
            ->putJson(route('masters.areas.quick-update', $areaId), ['name' => 'Punjab South'])
            ->assertOk()
            ->assertJsonPath('item.name', 'Punjab South');

        $route = $this->actingAs($user)
            ->postJson(route('masters.routes.quick-store'), [
                'name' => 'Ludhiana Route C',
                'area_id' => $areaId,
            ])
            ->assertCreated();

        $routeId = $route->json('item.id');
        $this->assertSame($areaId, $route->json('item.area_id'));

        $party = Customer::create([
            'name' => 'Route Party',
            'code' => 'RP-1',
            'customer_type_id' => $type->id,
            'area_id' => $areaId,
            'route_id' => $routeId,
            'company_id' => $user->company_id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('masters.routes.quick-destroy', $routeId))
            ->assertOk();

        $this->assertNull(Route::query()->find($routeId));
        $this->assertNull($party->fresh()->route_id);

        $this->actingAs($user)
            ->deleteJson(route('masters.areas.quick-destroy', $areaId))
            ->assertOk();

        $this->assertNull(Area::query()->find($areaId));
        $this->assertNull($party->fresh()->area_id);
    }

    public function test_an_area_used_by_a_region_brand_policy_cannot_be_deleted(): void
    {
        $user = $this->partyUser();
        $area = Area::create(['name' => 'Locked Area', 'code' => 'LOCK', 'is_active' => true]);
        $brand = new Brand;
        $brand->forceFill([
            'company_id' => $user->company_id,
            'name' => 'Locked Brand',
            'is_active' => true,
        ])->save();

        RegionBrandPolicy::create([
            'area_id' => $area->id,
            'brand_id' => $brand->id,
            'is_allowed' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('masters.areas.quick-destroy', $area))
            ->assertStatus(422);

        $this->assertNotNull(Area::query()->find($area->id));
    }

    private function partyUser(): User
    {
        $company = Company::create([
            'name' => 'City Test Co',
            'code' => 'CTC',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        return $user;
    }
}
