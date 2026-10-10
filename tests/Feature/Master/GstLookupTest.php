<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GstLookupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        foreach (['masters.view', 'masters.create', 'masters.edit', 'masters.manage', 'customers.view', 'customers.create', 'customers.edit'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->company = Company::firstOrCreate(
            ['code' => 'TESTCO'],
            ['name' => 'Test Company', 'currency' => 'INR', 'is_active' => true]
        );

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($superAdminRole);
        $this->user->givePermissionTo(['masters.view', 'masters.create', 'masters.edit', 'masters.manage', 'customers.view', 'customers.create', 'customers.edit']);
    }

    /**
     * Requirement 1: Valid GSTIN with a successful Sandbox API response populates the correct fields.
     */
    public function test_valid_gstin_returns_verified_party_details(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response([
                'access_token' => 'mock-jwt-token-xyz',
            ], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'code' => 200,
                'data' => [
                    'gstin' => '27AAAAA0000A1Z5',
                    'trade_name' => 'Apex Solutions',
                    'legal_name' => 'Apex Solutions Private Limited',
                    'status' => 'Active',
                    'registration_date' => '01/07/2017',
                    'taxpayer_type' => 'Regular',
                    'principal_place_of_business' => [
                        'address' => [
                            'building_number' => 'Flat 402',
                            'building_name' => 'Galaxy Tower',
                            'street' => 'Senapati Bapat Marg',
                            'location' => 'Dadar West',
                            'city' => 'Mumbai',
                            'district' => 'Mumbai',
                            'state' => 'Maharashtra',
                            'pincode' => '400028',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_live' => true,
            'party' => [
                'gstin' => '27AAAAA0000A1Z5',
                'name' => 'Apex Solutions',
                'trade_name' => 'Apex Solutions',
                'legal_name' => 'Apex Solutions Private Limited',
                'pan' => 'AAAAA0000A',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'pincode' => '400028',
                'status' => 'Active',
            ],
        ]);

        $this->assertStringContainsString('Flat 402', $response->json('party.address'));
        $this->assertStringContainsString('Galaxy Tower', $response->json('party.address'));
        $this->assertStringContainsString('Senapati Bapat Marg', $response->json('party.address'));

        // Verify request headers sent to Sandbox search endpoint
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if ($request->url() === 'https://api.sandbox.co.in/gst/compliance/public/gstin/search') {
                return $request->hasHeader('x-api-key', 'valid-sandbox-key')
                    && $request->hasHeader('authorization', 'mock-jwt-token-xyz')
                    && $request->hasHeader('x-api-version', '1.0.0')
                    && $request['gstin'] === '27AAAAA0000A1Z5';
            }
            return true;
        });
    }

    /**
     * Requirement 1B: Sandbox token caching - multiple requests do not repeatedly call /authenticate.
     */
    public function test_sandbox_access_token_is_cached(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response([
                'access_token' => 'cached-jwt-token-123',
            ], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'code' => 200,
                'data' => [
                    'gstin' => '27AAAAA0000A1Z5',
                    'trade_name' => 'Cached Entity',
                    'legal_name' => 'Cached Entity Pvt Ltd',
                    'status' => 'Active',
                    'address' => ['state' => 'Maharashtra'],
                ],
            ], 200),
        ]);

        $resp1 = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));
        $resp1->assertStatus(200);

        $resp2 = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));
        $resp2->assertStatus(200);

        // /authenticate should only have been called once due to caching
        Http::assertSentCount(3); // 1 authenticate + 2 searches
    }

    /**
     * Requirement 2: Trade name is preferred, with legal name as fallback.
     */
    public function test_trade_name_is_preferred_and_legal_name_is_used_as_fallback(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        // Case A & B: trade name preferred when present, legal name fallback when blank
        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-1'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => function (\Illuminate\Http\Client\Request $request) {
                if (($request['gstin'] ?? '') === '27BBBBA0000B1Z5') {
                    return Http::response([
                        'data' => [
                            'gstin' => '27BBBBA0000B1Z5',
                            'trade_name' => '',
                            'legal_name' => 'Corporate Legal Name Pvt Ltd',
                            'status' => 'Active',
                            'address' => ['state' => 'Maharashtra'],
                        ],
                    ], 200);
                }

                return Http::response([
                    'data' => [
                        'gstin' => '27AAAAA0000A1Z5',
                        'trade_name' => 'Preferred Brand',
                        'legal_name' => 'Corporate Legal Name Pvt Ltd',
                        'status' => 'Active',
                        'address' => ['state' => 'Maharashtra'],
                    ],
                ], 200);
            },
        ]);

        $respA = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));
        $respA->assertStatus(200);
        $this->assertEquals('Preferred Brand', $respA->json('party.name'));

        $respB = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27BBBBA0000B1Z5']));
        $respB->assertStatus(200);
        $this->assertEquals('Corporate Legal Name Pvt Ltd', $respB->json('party.name'));
    }

    /**
     * Requirement 3: Missing optional address fields do not break the response.
     */
    public function test_missing_optional_address_fields_handled_cleanly(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        // Address with no building name or location
        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-xyz'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'data' => [
                    'gstin' => '27AAAAA0000A1Z5',
                    'trade_name' => 'Minimal Address Co',
                    'status' => 'Active',
                    'principal_place_of_business' => [
                        'address' => [
                            'city' => 'Pune',
                            'state' => 'Maharashtra',
                            'pincode' => '411001',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'party' => [
                'name' => 'Minimal Address Co',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411001',
            ],
        ]);
        $this->assertNotEmpty($response->json('party.address'));
    }

    /**
     * ClearTax provider format integration.
     */
    public function test_cleartax_provider_response_mapping(): void
    {
        config([
            'services.gst.provider' => 'cleartax',
            'services.gst.api_key' => 'cleartax-token-123',
        ]);

        Http::fake([
            'https://api.cleartax.in/enterprise/v1/gstin/29ABCDE1234F1Z5' => Http::response([
                'taxpayerInfo' => [
                    'gstin' => '29ABCDE1234F1Z5',
                    'tradeNam' => 'ClearTax Client Traders',
                    'lgnm' => 'ClearTax Client Traders LLP',
                    'sts' => 'Active',
                    'pradr' => [
                        'addr' => [
                            'bno' => 'Plot 12',
                            'bnm' => 'Tech Park',
                            'st' => 'Whitefield Main Road',
                            'loc' => 'Whitefield',
                            'dst' => 'Bengaluru',
                            'city' => 'Bengaluru',
                            'stcd' => 'Karnataka',
                            'pncd' => '560066',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '29ABCDE1234F1Z5']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_live' => true,
            'party' => [
                'gstin' => '29ABCDE1234F1Z5',
                'name' => 'ClearTax Client Traders',
                'pan' => 'ABCDE1234F',
                'state' => 'Karnataka',
                'city' => 'Bengaluru',
                'pincode' => '560066',
            ],
        ]);
    }

    /**
     * Masters India provider format integration.
     */
    public function test_mastersindia_provider_response_mapping(): void
    {
        config([
            'services.gst.provider' => 'mastersindia',
            'services.gst.api_key' => 'mastersindia-direct-token',
        ]);

        Http::fake([
            '*searchTaxpayer*' => Http::response([
                'status_code' => 200,
                'data' => [
                    'gstin' => '27AAACG9999A1Z9',
                    'tradeNam' => 'Masters India Vendor',
                    'lgnm' => 'Masters India Vendor Pvt Ltd',
                    'sts' => 'Active',
                    'pradr' => [
                        'addr' => [
                            'bno' => '501',
                            'bnm' => 'Business Hub',
                            'st' => 'Andheri East',
                            'dst' => 'Mumbai',
                            'city' => 'Mumbai',
                            'stcd' => '27',
                            'pncd' => '400069',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAACG9999A1Z9']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'party' => [
                'name' => 'Masters India Vendor',
                'pan' => 'AAACG9999A',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'pincode' => '400069',
            ],
        ]);
    }

    /**
     * Requirement 4: Invalid GSTIN inputs produce clear error messages.
     */
    public function test_invalid_gstin_length_and_pattern_produce_clear_messages(): void
    {
        // 1. Missing parameter
        $respEmpty = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup'));
        $respEmpty->assertStatus(422);
        $respEmpty->assertJson(['success' => false, 'message' => 'GSTIN parameter is required.']);

        // 2. Short length
        $respShort = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA000']));
        $respShort->assertStatus(422);
        $this->assertStringContainsString('valid 15-character GSTIN', $respShort->json('message'));

        // 3. Invalid characters
        $respPattern = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1$$']));
        $respPattern->assertStatus(422);
        $this->assertStringContainsString('Invalid GSTIN format', $respPattern->json('message'));

        // 4. Invalid state code (e.g. 00 or 99)
        $respState = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup', ['gstin' => '99AAAAA0000A1Z5']));
        $respState->assertStatus(422);
        $this->assertStringContainsString('Invalid GSTIN state code', $respState->json('message'));
    }

    /**
     * Requirement 5: GSTIN not found by provider returns 404 with helpful message.
     */
    /**
     * Requirement 5: GSTIN not found by provider returns 404 with helpful message.
     */
    public function test_gstin_not_found_handled_correctly(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-123'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'code' => 404,
                'message' => 'GSTIN not found in GST database',
            ], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('not found', strtolower($response->json('message')));
    }

    /**
     * Requirement 6A: Provider authentication failure handled gracefully without exposing secrets.
     */
    public function test_provider_authentication_failure_handled_gracefully(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'expired-secret-key-12345',
            'services.gst.api_secret' => 'invalid-secret-value-67890',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response([
                'message' => 'Unauthorized or invalid API credentials',
            ], 401),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('authentication failed', strtolower($response->json('message')));
        // Never expose secret API credentials in the response
        $this->assertStringNotContainsString('expired-secret-key-12345', $response->getContent());
        $this->assertStringNotContainsString('invalid-secret-value-67890', $response->getContent());
    }

    /**
     * Requirement 6B: Provider rate limit handled gracefully.
     */
    public function test_provider_rate_limit_handled_gracefully(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-key',
            'services.gst.api_secret' => 'valid-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-123'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'message' => 'Too Many Requests',
            ], 429),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('rate limit', strtolower($response->json('message')));
    }

    /**
     * Requirement 6C: Provider timeout handled gracefully.
     */
    public function test_provider_timeout_handled_gracefully(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-key',
            'services.gst.api_secret' => 'valid-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(504);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('timed out', strtolower($response->json('message')));
    }

    /**
     * Requirement 6D: Missing credentials handled with informative message.
     */
    public function test_missing_credentials_returns_clear_configuration_notice(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => null,
            'services.gst.api_secret' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'is_configured' => false,
        ]);
        $this->assertStringContainsString('not configured', strtolower($response->json('message')));
        $this->assertContains('GST_API_KEY', $response->json('required_keys'));
        $this->assertContains('GST_API_SECRET', $response->json('required_keys'));

        // Also test partial credentials (key without secret)
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'key-without-secret',
            'services.gst.api_secret' => null,
        ]);

        $partialResponse = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $partialResponse->assertStatus(503);
        $partialResponse->assertJson([
            'success' => false,
            'is_configured' => false,
        ]);
    }

    /**
     * Requirement 8: Fetching GST details does NOT automatically save the party to database.
     */
    public function test_gst_lookup_does_not_persist_record_to_database(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-key',
            'services.gst.api_secret' => 'valid-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-123'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'data' => [
                    'gstin' => '27AAAAA0000A1Z5',
                    'trade_name' => 'Unsaved Temporary Entity',
                    'status' => 'Active',
                    'address' => ['state' => 'Maharashtra'],
                ],
            ], 200),
        ]);

        $this->assertEquals(0, Customer::where('gstin', '27AAAAA0000A1Z5')->count());

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(200);

        // Database count must still be 0!
        $this->assertEquals(0, Customer::where('gstin', '27AAAAA0000A1Z5')->count());
    }

    /**
     * Requirement 9: Regular party creation with verified GST details saves properly.
     */
    public function test_party_creation_with_gstin_persists_properly(): void
    {
        $customerType = \App\Domains\Master\Models\CustomerType::create([
            'name' => 'Wholesaler',
            'code' => 'WHL',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), [
            'name' => 'Verified Acme Traders',
            'customer_type_id' => $customerType->id,
            'gstin' => '27AAAAA0000A1Z5',
            'pan' => 'AAAAA0000A',
            'party_type' => 'sundry_debtors',
            'state' => 'Maharashtra',
            'phone' => '9876543210',
            'email' => 'acme@example.com',
            'address' => '101 Galaxy Towers, Dadar, Mumbai',
            'pincode' => '400028',
            'addresses' => [
                [
                    'label' => 'Head Office',
                    'type' => 'both',
                    'address_line_1' => '101 Galaxy Towers',
                    'city' => 'Mumbai',
                    'state' => 'Maharashtra',
                    'pincode' => '400028',
                    'is_default_billing' => 1,
                    'is_default_delivery' => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('masters.customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Verified Acme Traders',
            'gstin' => '27AAAAA0000A1Z5',
            'state' => 'Maharashtra',
        ]);
    }
}
