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
                    'data' => [
                        'gstin' => '27AAAAA0000A1Z2',
                        'tradeNam' => 'Apex Solutions',
                        'lgnm' => 'Apex Solutions Private Limited',
                        'sts' => 'Active',
                        'rgdt' => '01/07/2017',
                        'dty' => 'Regular',
                        'ctb' => 'Private Limited Company',
                        'nba' => ['Wholesale Business'],
                        'stj' => 'MUM-VAT-001',
                        'ctj' => 'RANGE-MUM-01',
                        'pradr' => [
                            'addr' => [
                                'bno' => 'Flat 402',
                                'bnm' => 'Galaxy Tower',
                                'st' => 'Senapati Bapat Marg',
                                'loc' => 'Dadar West',
                                'city' => 'Mumbai',
                                'dst' => 'Mumbai',
                                'stcd' => 'Maharashtra',
                                'pncd' => '400028',
                            ],
                            'ntr' => ['Wholesale Business'],
                        ],
                        'adadr' => [],
                    ],
                    'status_cd' => '1',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_live' => true,
            'party' => [
                'gstin' => '27AAAAA0000A1Z2',
                'name' => 'Apex Solutions Private Limited',
                'trade_name' => 'Apex Solutions',
                'legal_name' => 'Apex Solutions Private Limited',
                'pan' => 'AAAAA0000A',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'pincode' => '400028',
                'status' => 'Active',
                'constitution_of_business' => 'Private Limited Company',
                'taxpayer_type' => 'Regular',
                'registration_date' => '01/07/2017',
            ],
        ]);

        $this->assertStringContainsString('Flat 402', $response->json('party.address'));
        $this->assertStringContainsString('Galaxy Tower', $response->json('party.address'));
        $this->assertStringContainsString('Senapati Bapat Marg', $response->json('party.address'));
        $this->assertEquals(['Wholesale Business'], $response->json('party.nature_of_business'));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if ($request->url() === 'https://api.sandbox.co.in/gst/compliance/public/gstin/search') {
                return $request->hasHeader('x-api-key', 'valid-sandbox-key')
                    && $request->hasHeader('authorization', 'mock-jwt-token-xyz')
                    && $request->hasHeader('x-api-version', '1.0')
                    && $request['gstin'] === '27AAAAA0000A1Z2';
            }

            return true;
        });
    }

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
                    'data' => [
                        'gstin' => '27AAAAA0000A1Z2',
                        'tradeNam' => 'Cached Entity',
                        'lgnm' => 'Cached Entity Pvt Ltd',
                        'sts' => 'Active',
                        'pradr' => ['addr' => ['stcd' => 'Maharashtra']],
                    ],
                ],
            ], 200),
        ]);

        $resp1 = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));
        $resp1->assertStatus(200);

        $resp2 = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));
        $resp2->assertStatus(200);

        Http::assertSentCount(3); // 1 authenticate + 2 searches
    }

    public function test_legal_name_is_preferred_and_trade_name_is_fallback(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-1'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => function (\Illuminate\Http\Client\Request $request) {
                if (($request['gstin'] ?? '') === '27BBBBA0000B1ZU') {
                    return Http::response([
                        'data' => [
                            'data' => [
                                'gstin' => '27BBBBA0000B1ZU',
                                'tradeNam' => 'Only Trade Name',
                                'lgnm' => '',
                                'sts' => 'Active',
                                'pradr' => ['addr' => ['stcd' => 'Maharashtra']],
                            ],
                        ],
                    ], 200);
                }

                return Http::response([
                    'data' => [
                        'data' => [
                            'gstin' => '27AAAAA0000A1Z2',
                            'tradeNam' => 'Preferred Brand',
                            'lgnm' => 'Corporate Legal Name Pvt Ltd',
                            'sts' => 'Active',
                            'pradr' => ['addr' => ['stcd' => 'Maharashtra']],
                        ],
                    ],
                ], 200);
            },
        ]);

        $respA = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));
        $respA->assertStatus(200);
        $this->assertEquals('Corporate Legal Name Pvt Ltd', $respA->json('party.name'));
        $this->assertEquals('Preferred Brand', $respA->json('party.trade_name'));

        $respB = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27BBBBA0000B1ZU']));
        $respB->assertStatus(200);
        $this->assertEquals('Only Trade Name', $respB->json('party.name'));
    }

    public function test_missing_optional_address_fields_handled_cleanly(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-xyz'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'data' => [
                    'data' => [
                        'gstin' => '27AAAAA0000A1Z2',
                        'tradeNam' => 'Minimal Address Co',
                        'lgnm' => 'Minimal Address Co',
                        'sts' => 'Active',
                        'pradr' => [
                            'addr' => [
                                'loc' => 'Pune',
                                'stcd' => 'Maharashtra',
                                'pncd' => '411001',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

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

    public function test_additional_places_of_business_are_returned(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-xyz'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'data' => [
                    'data' => [
                        'gstin' => '27AAAAA0000A1Z2',
                        'lgnm' => 'Multi Location Pvt Ltd',
                        'tradeNam' => 'Multi Location',
                        'sts' => 'Active',
                        'pradr' => [
                            'addr' => [
                                'bno' => '1',
                                'bnm' => 'Main Building',
                                'st' => 'MG Road',
                                'loc' => 'Pune',
                                'stcd' => 'Maharashtra',
                                'pncd' => '411001',
                            ],
                        ],
                        'adadr' => [
                            [
                                'addr' => [
                                    'bno' => '22',
                                    'bnm' => 'Warehouse',
                                    'st' => 'MIDC Road',
                                    'loc' => 'Nashik',
                                    'stcd' => 'Maharashtra',
                                    'pncd' => '422001',
                                ],
                                'ntr' => ['Warehouse / Depot'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(200);
        $addresses = $response->json('party.addresses');
        $this->assertCount(2, $addresses);
        $this->assertEquals('Principal Place of Business', $addresses[0]['label']);
        $this->assertEquals('Additional Place of Business 1', $addresses[1]['label']);
        $this->assertEquals('422001', $addresses[1]['pincode']);
    }

    public function test_cancelled_registration_is_flagged(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-xyz'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'data' => [
                    'data' => [
                        'gstin' => '27AAAAA0000A1Z2',
                        'lgnm' => 'Cancelled Traders',
                        'tradeNam' => 'Cancelled Traders',
                        'sts' => 'Cancelled',
                        'cxdt' => '01/01/2024',
                        'pradr' => ['addr' => ['stcd' => 'Maharashtra', 'loc' => 'Mumbai']],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'party' => [
                'status' => 'Cancelled',
                'cancellation_date' => '01/01/2024',
                'is_inactive' => true,
            ],
        ]);
    }

    public function test_fo8000_no_records_is_not_treated_as_success(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-xyz'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'code' => 200,
                'data' => [
                    'message' => 'No records found',
                    'error_cd' => 'FO8000',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAPFU0939F1ZV']));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('no records', strtolower($response->json('message')));
        $this->assertEquals('AAPFU0939F', $response->json('extracted.pan'));
        $this->assertEquals('Maharashtra', $response->json('extracted.state'));
    }

    public function test_http_400_surfaces_provider_message(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        // Bypass local checksum so the mocked provider 400 is exercised.
        $gstin = '27AAAAA0000A1Z2';

        Http::fake([
            'https://api.sandbox.co.in/authenticate' => Http::response(['access_token' => 'token-xyz'], 200),
            'https://api.sandbox.co.in/gst/compliance/public/gstin/search' => Http::response([
                'code' => 400,
                'message' => 'Invalid GSTIN pattern',
            ], 400),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => $gstin]));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid GSTIN pattern',
        ]);
        $this->assertStringNotContainsString('valid-sandbox-key', $response->getContent());
        $this->assertStringNotContainsString('valid-sandbox-secret', $response->getContent());
    }

    public function test_invalid_checksum_rejected_before_provider_call(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'valid-sandbox-key',
            'services.gst.api_secret' => 'valid-sandbox-secret',
        ]);

        Http::fake();

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z5']));

        $response->assertStatus(422);
        $this->assertStringContainsString('checksum', strtolower($response->json('message')));
        Http::assertNothingSent();
    }

    public function test_cleartax_provider_response_mapping(): void
    {
        config([
            'services.gst.provider' => 'cleartax',
            'services.gst.api_key' => 'cleartax-token-123',
            'services.gst.api_url' => 'https://api.cleartax.in',
        ]);

        Http::fake([
            'api.cleartax.in/*' => Http::response([
                'taxpayerInfo' => [
                    'gstin' => '29ABCDE1234F1ZW',
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
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '29ABCDE1234F1ZW']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_live' => true,
            'party' => [
                'gstin' => '29ABCDE1234F1ZW',
                'name' => 'ClearTax Client Traders LLP',
                'pan' => 'ABCDE1234F',
                'state' => 'Karnataka',
                'city' => 'Bengaluru',
                'pincode' => '560066',
            ],
        ]);
    }

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
                    'gstin' => '27AAACG9999A1ZA',
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
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAACG9999A1ZA']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'party' => [
                'name' => 'Masters India Vendor Pvt Ltd',
                'pan' => 'AAACG9999A',
                'state' => 'Maharashtra',
                'city' => 'Mumbai',
                'pincode' => '400069',
            ],
        ]);
    }

    public function test_invalid_gstin_length_and_pattern_produce_clear_messages(): void
    {
        $respEmpty = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup'));
        $respEmpty->assertStatus(422);
        $respEmpty->assertJson(['success' => false, 'message' => 'GSTIN parameter is required.']);

        $respShort = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA000']));
        $respShort->assertStatus(422);
        $this->assertStringContainsString('valid 15-character GSTIN', $respShort->json('message'));

        $respPattern = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1$$']));
        $respPattern->assertStatus(422);
        $this->assertStringContainsString('Invalid GSTIN format', $respPattern->json('message'));

        $respState = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup', ['gstin' => '99AAAAA0000A1Z5']));
        $respState->assertStatus(422);
        $this->assertStringContainsString('Invalid GSTIN state code', $respState->json('message'));
    }

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
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('not found', strtolower($response->json('message')));
    }

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
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('authentication failed', strtolower($response->json('message')));
        $this->assertStringNotContainsString('expired-secret-key-12345', $response->getContent());
        $this->assertStringNotContainsString('invalid-secret-value-67890', $response->getContent());
    }

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
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('rate limit', strtolower($response->json('message')));
    }

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
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(504);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('timed out', strtolower($response->json('message')));
    }

    public function test_missing_credentials_returns_clear_configuration_notice(): void
    {
        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => null,
            'services.gst.api_secret' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'is_configured' => false,
        ]);
        $this->assertStringContainsString('not configured', strtolower($response->json('message')));
        $this->assertContains('GST_API_KEY', $response->json('required_keys'));
        $this->assertContains('GST_API_SECRET', $response->json('required_keys'));

        config([
            'services.gst.provider' => 'sandbox',
            'services.gst.api_key' => 'key-without-secret',
            'services.gst.api_secret' => null,
        ]);

        $partialResponse = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $partialResponse->assertStatus(503);
        $partialResponse->assertJson([
            'success' => false,
            'is_configured' => false,
        ]);
    }

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
                    'data' => [
                        'gstin' => '27AAAAA0000A1Z2',
                        'tradeNam' => 'Unsaved Temporary Entity',
                        'lgnm' => 'Unsaved Temporary Entity',
                        'sts' => 'Active',
                        'pradr' => ['addr' => ['stcd' => 'Maharashtra']],
                    ],
                ],
            ], 200),
        ]);

        $this->assertEquals(0, Customer::where('gstin', '27AAAAA0000A1Z2')->count());

        $response = $this->actingAs($this->user)
            ->getJson(route('masters.customers.gst-lookup', ['gstin' => '27AAAAA0000A1Z2']));

        $response->assertStatus(200);
        $this->assertEquals(0, Customer::where('gstin', '27AAAAA0000A1Z2')->count());
    }

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
            'gstin' => '27AAAAA0000A1Z2',
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
            'gstin' => '27AAAAA0000A1Z2',
            'state' => 'Maharashtra',
        ]);
    }
}
