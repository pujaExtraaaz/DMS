<?php

namespace App\Domains\Master\Services;

use App\Support\IndianStates;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GstLookupService
{
    /**
     * Standard 15-character GSTIN regex:
     * 2 digits state code + 10 alphanumeric PAN + 1 entity code + 1 alphanumeric + 1 alphanumeric checksum
     */
    public const GSTIN_REGEX = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[0-9A-Z]{1}[0-9A-Z]{1}$/i';

    /**
     * Validate GSTIN length and alphanumeric pattern.
     */
    public static function isValidGstin(?string $gstin): bool
    {
        if (! $gstin) {
            return false;
        }

        $clean = strtoupper(trim($gstin));

        return strlen($clean) === 15 && (bool) preg_match(self::GSTIN_REGEX, $clean);
    }

    /**
     * Check if the configured provider has the required credentials.
     */
    public function isConfigured(?string $provider = null): bool
    {
        $provider = strtolower((string) ($provider ?? config('services.gst.provider', 'sandbox')));

        return match ($provider) {
            'sandbox' => (filled(config('services.gst.api_key')) && filled(config('services.gst.api_secret')))
                || str_starts_with((string) config('services.gst.api_key'), 'eyJ')
                || str_starts_with((string) config('services.gst.api_secret'), 'eyJ'),
            'cleartax' => filled(config('services.gst.api_key')),
            'mastersindia' => filled(config('services.gst.api_key'))
                || (
                    filled(config('services.mastersindia.client_id'))
                    && filled(config('services.mastersindia.client_secret'))
                    && filled(config('services.mastersindia.username'))
                    && filled(config('services.mastersindia.password'))
                ),
            'custom' => filled(config('services.gst.api_url')),
            default => filled(config('services.gst.api_key')),
        };
    }

    /**
     * Search GSTIN against authorized GST verification provider.
     *
     * @return array{
     *     success: bool,
     *     is_live?: bool,
     *     is_configured?: bool,
     *     status_code?: int,
     *     message: string,
     *     party?: array{
     *         gstin: string,
     *         name: string,
     *         trade_name: string,
     *         legal_name: string,
     *         pan: string,
     *         state: string,
     *         city: string,
     *         pincode: string,
     *         address: string,
     *         address_line_1: string,
     *         address_line_2: string,
     *         status: string,
     *         registration_date?: ?string,
     *         taxpayer_type?: ?string
     *     },
     *     required_keys?: array<int, string>
     * }
     */
    public function search(string $gstin): array
    {
        $gstin = strtoupper(trim($gstin));

        // 1. Length check
        if (strlen($gstin) !== 15) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Please enter a valid 15-character GSTIN (e.g. 27AAAAA0000A1Z5).',
            ];
        }

        // 2. Format validation
        if (! self::isValidGstin($gstin)) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Invalid GSTIN format. A valid GSTIN consists of 2-digit state code, 10-character PAN, 1 entity digit, and checksum.',
            ];
        }

        // 3. State code validation
        $stateCode = substr($gstin, 0, 2);
        if (! isset(IndianStates::all()[$stateCode])) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => "Invalid GSTIN state code '{$stateCode}'. State code must be between 01 and 38 (or 97).",
            ];
        }

        $provider = strtolower((string) config('services.gst.provider', 'sandbox'));

        // 4. Missing credentials check
        if (! $this->isConfigured($provider)) {
            return [
                'success' => false,
                'is_configured' => false,
                'status_code' => 503,
                'message' => "GST verification API is not configured. Please configure {$this->getRequiredCredentialsName($provider)} in your environment.",
                'provider' => $provider,
                'required_keys' => $this->getRequiredConfigKeys($provider),
            ];
        }

        // 5. Query the authorized provider
        try {
            $response = match ($provider) {
                'sandbox' => $this->callSandbox($gstin),
                'cleartax' => $this->callClearTax($gstin),
                'mastersindia' => $this->callMastersIndia($gstin),
                default => $this->callCustom($gstin),
            };
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'status_code' => 401,
                'message' => $e->getMessage(),
            ];
        } catch (ConnectionException $e) {
            Log::warning("GST API connection timeout for GSTIN {$gstin}: " . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 504,
                'message' => 'GST verification service timed out. Please try again or enter party details manually.',
            ];
        } catch (\Throwable $e) {
            Log::warning("GST API unexpected error for GSTIN {$gstin}: " . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 502,
                'message' => 'Unable to connect to GST verification provider. Please try again later or enter details manually.',
            ];
        }

        // 6. Handle HTTP response status
        return $this->handleProviderResponse($response, $gstin, $provider);
    }

    /**
     * Obtain or retrieve cached Sandbox access token.
     * Uses official Sandbox POST /authenticate endpoint with x-api-key and x-api-secret.
     * Tokens are valid for 24 hours and cached for 23 hours.
     */
    protected function getSandboxAccessToken(string $baseUrl): string
    {
        $apiKey = (string) config('services.gst.api_key');
        $apiSecret = (string) config('services.gst.api_secret');

        // If either API key or secret is already a JWT token, use it directly
        if (str_starts_with($apiKey, 'eyJ')) {
            return $apiKey;
        }
        if (str_starts_with($apiSecret, 'eyJ')) {
            return $apiSecret;
        }

        if (blank($apiSecret)) {
            return $apiKey;
        }

        $cacheKey = 'sandbox:gst_lookup_token:' . md5($apiKey . $apiSecret);

        return Cache::remember($cacheKey, now()->addHours(23), function () use ($baseUrl, $apiKey, $apiSecret) {
            $timeout = (int) config('services.gst.timeout', 15);
            $authResponse = Http::timeout($timeout)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'x-api-secret' => $apiSecret,
                    'x-api-version' => '1.0.0',
                    'Accept' => 'application/json',
                ])
                ->post("{$baseUrl}/authenticate");

            if ($authResponse->status() === 401 || $authResponse->status() === 403) {
                Log::warning("Sandbox API authentication failure at /authenticate: HTTP {$authResponse->status()}");
                throw new \InvalidArgumentException('GST verification service authentication failed. Please check provider API credentials.');
            }

            if (! $authResponse->successful()) {
                Log::warning("Sandbox API authentication error: HTTP {$authResponse->status()} - {$authResponse->body()}");
                throw new \RuntimeException('Sandbox authentication failed: ' . ($authResponse->json('message') ?? "HTTP {$authResponse->status()}"));
            }

            $token = (string) (
                $authResponse->json('access_token')
                ?? $authResponse->json('data.access_token')
                ?? $authResponse->json('token')
                ?? ''
            );

            if (blank($token)) {
                throw new \RuntimeException('No access token returned from Sandbox authentication.');
            }

            return $token;
        });
    }

    /**
     * Sandbox.co.in GSTIN verification search API.
     * Documented flow:
     * - Authenticate via POST /authenticate with x-api-key, x-api-secret, and x-api-version to obtain JWT access token.
     * - Query POST /gst/compliance/public/gstin/search with x-api-key, authorization (token without Bearer), and x-api-version.
     */
    protected function callSandbox(string $gstin): Response
    {
        $baseUrl = rtrim((string) (config('services.gst.api_url') ?: 'https://api.sandbox.co.in'), '/');
        $timeout = (int) config('services.gst.timeout', 15);
        $apiKey = (string) config('services.gst.api_key');

        $accessToken = $this->getSandboxAccessToken($baseUrl);

        $headers = [
            'x-api-key' => $apiKey,
            'authorization' => $accessToken,
            'x-api-version' => '1.0.0',
            'Accept' => 'application/json',
        ];

        $customUrl = (string) config('services.gst.api_url');
        if (filled($customUrl) && str_contains($customUrl, '{gstin}')) {
            $endpoint = str_replace('{gstin}', $gstin, $customUrl);
            return Http::timeout($timeout)->withHeaders($headers)->get($endpoint);
        }

        $endpoint = "{$baseUrl}/gst/compliance/public/gstin/search";

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->asJson()
            ->post($endpoint, ['gstin' => $gstin]);

        // Fallback to legacy GET /gsp/public/gstin/{gstin} if customUrl explicitly targets /gsp/
        if ($response->status() === 404 && filled($customUrl) && str_contains($customUrl, '/gsp/')) {
            $fallbackEndpoint = "{$baseUrl}/gsp/public/gstin/{$gstin}";
            return Http::timeout($timeout)->withHeaders($headers)->get($fallbackEndpoint);
        }

        return $response;
    }

    /**
     * ClearTax (Clear) Enterprise GSTIN verification API.
     */
    protected function callClearTax(string $gstin): Response
    {
        $baseUrl = rtrim((string) (config('services.gst.api_url') ?: 'https://api.cleartax.in'), '/');
        $endpoint = "{$baseUrl}/enterprise/v1/gstin/{$gstin}";
        $timeout = (int) config('services.gst.timeout', 15);

        return Http::timeout($timeout)
            ->withHeaders([
                'x-cleartax-auth-token' => (string) config('services.gst.api_key'),
                'Accept' => 'application/json',
            ])
            ->get($endpoint);
    }

    /**
     * Masters India search taxpayer API.
     */
    protected function callMastersIndia(string $gstin): Response
    {
        $baseUrl = rtrim((string) (config('services.mastersindia.base_url') ?: config('services.gst.api_url') ?: 'https://commonapi.mastersindia.co'), '/');
        $endpoint = "{$baseUrl}/commonApi/searchTaxpayer";
        $timeout = (int) config('services.gst.timeout', config('services.mastersindia.timeout', 15));

        $token = (string) config('services.gst.api_key');
        if (blank($token)) {
            $token = $this->getMastersIndiaOauthToken($baseUrl);
        }

        return Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => "Bearer {$token}",
                'client_id' => (string) config('services.mastersindia.client_id'),
                'Accept' => 'application/json',
            ])
            ->get($endpoint, ['gstin' => $gstin]);
    }

    /**
     * Custom / Generic REST GST endpoint.
     */
    protected function callCustom(string $gstin): Response
    {
        $url = (string) config('services.gst.api_url');
        $timeout = (int) config('services.gst.timeout', 15);

        $endpoint = str_contains($url, '{gstin}') ? str_replace('{gstin}', $gstin, $url) : $url;
        $params = str_contains($url, '{gstin}') ? [] : ['gstin' => $gstin];

        $headers = ['Accept' => 'application/json'];
        $key = (string) config('services.gst.api_key');
        if (filled($key)) {
            $headers['Authorization'] = str_starts_with($key, 'Bearer ') ? $key : "Bearer {$key}";
            $headers['x-api-key'] = $key;
        }

        return Http::timeout($timeout)
            ->withHeaders($headers)
            ->get($endpoint, $params);
    }

    /**
     * Obtain or retrieve cached Masters India OAuth token.
     */
    protected function getMastersIndiaOauthToken(string $baseUrl): string
    {
        $clientId = (string) config('services.mastersindia.client_id');
        $cacheKey = 'mastersindia:gst_lookup_token:' . md5($clientId);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($baseUrl) {
            $resp = Http::baseUrl($baseUrl)
                ->timeout((int) config('services.mastersindia.timeout', 15))
                ->asJson()
                ->post('/oauth/token', [
                    'username' => config('services.mastersindia.username'),
                    'password' => config('services.mastersindia.password'),
                    'client_id' => config('services.mastersindia.client_id'),
                    'client_secret' => config('services.mastersindia.client_secret'),
                    'grant_type' => 'password',
                ]);

            if (! $resp->ok()) {
                throw new \RuntimeException('Masters India authentication failed: ' . $resp->status());
            }

            return (string) ($resp->json('access_token') ?? $resp->json('data.access_token') ?? '');
        });
    }

    /**
     * Process HTTP response and return clean, normalized party details.
     */
    protected function handleProviderResponse(Response $response, string $gstin, string $provider): array
    {
        $status = $response->status();

        if ($status === 404) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => "GSTIN {$gstin} was not found or is not registered.",
            ];
        }

        if ($status === 401 || $status === 403) {
            Log::warning("GST API auth failure for provider [{$provider}]: HTTP {$status}");

            return [
                'success' => false,
                'status_code' => 401,
                'message' => 'GST verification service authentication failed. Please check provider API credentials.',
            ];
        }

        if ($status === 429) {
            return [
                'success' => false,
                'status_code' => 429,
                'message' => 'GST verification API rate limit reached. Please wait a moment and try again.',
            ];
        }

        if ($response->serverError()) {
            Log::warning("GST API server error for provider [{$provider}]: HTTP {$status}");

            return [
                'success' => false,
                'status_code' => $status,
                'message' => 'GST verification service is temporarily unavailable. Please enter details manually or retry later.',
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'status_code' => $status,
                'message' => "GST lookup failed with status {$status}. Please enter details manually.",
            ];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return [
                'success' => false,
                'status_code' => 502,
                'message' => 'Invalid response received from GST lookup provider.',
            ];
        }

        // Check for error keys in 200 responses
        if (isset($json['status_cd']) && (string) $json['status_cd'] === '0') {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => (string) ($json['status_desc'] ?? $json['error'] ?? "GSTIN {$gstin} not found."),
            ];
        }

        if (isset($json['code']) && (int) $json['code'] === 404) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => (string) ($json['message'] ?? "GSTIN {$gstin} was not found or is not registered."),
            ];
        }

        if (isset($json['error']) && ! empty($json['error'])) {
            $msg = is_array($json['error']) ? ($json['error']['message'] ?? "GSTIN {$gstin} lookup failed.") : (string) $json['error'];

            return [
                'success' => false,
                'status_code' => 404,
                'message' => $msg,
            ];
        }

        // Extract taxpayer data block
        $data = $json['data'] ?? $json['taxpayerInfo'] ?? $json['taxpayer_info'] ?? $json;

        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        if (empty($data) || (! isset($data['tradeNam']) && ! isset($data['lgnm']) && ! isset($data['trade_name']) && ! isset($data['legal_name']))) {
            // If data is empty or missing name identifiers
            if (isset($json['message']) && is_string($json['message'])) {
                return [
                    'success' => false,
                    'status_code' => 404,
                    'message' => $json['message'],
                ];
            }
        }

        return $this->normalizeTaxpayerData($data, $gstin);
    }

    /**
     * Normalize raw taxpayer data to standard DMS party fields:
     * - Party Name: Prefer registered trade name; fallback to legal name
     * - PAN: Verified PAN or derived from GSTIN
     * - State: Principal place of business state name or from state code
     * - Address: Street, city, district, state, pincode
     * - Status: Active / Cancelled / Suspended
     */
    protected function normalizeTaxpayerData(array $data, string $gstin): array
    {
        $tradeName = trim((string) ($data['tradeNam'] ?? $data['trade_name'] ?? $data['tradeName'] ?? $data['trade_nm'] ?? ''));
        $legalName = trim((string) ($data['lgnm'] ?? $data['legal_name'] ?? $data['legalName'] ?? $data['legal_nm'] ?? ''));

        // Rule: Prefer trade name, fallback to legal name
        $name = $tradeName !== '' ? $tradeName : $legalName;
        if ($name === '') {
            $name = $legalName !== '' ? $legalName : $gstin;
        }

        // PAN: use verified PAN if returned, otherwise derive from valid GSTIN
        $pan = trim((string) ($data['pan'] ?? ''));
        if ($pan === '' || strlen($pan) !== 10) {
            $pan = substr($gstin, 2, 10);
        }

        // Registration status
        $status = trim((string) ($data['sts'] ?? $data['status'] ?? $data['gstin_status'] ?? 'Active'));

        // Address resolution
        $addr = $data['pradr']['addr']
            ?? $data['principal_place_of_business']['address']
            ?? $data['principal_place_of_business']
            ?? $data['address']
            ?? [];

        $addressLine1 = '';
        $addressLine2 = '';
        $city = '';
        $district = '';
        $pincode = '';
        $stateRaw = '';

        if (is_array($addr)) {
            $flno = trim((string) ($addr['flno'] ?? $addr['floor_number'] ?? $addr['floor_no'] ?? ''));
            $bno = trim((string) ($addr['bno'] ?? $addr['building_number'] ?? $addr['building_no'] ?? $addr['door_no'] ?? ''));
            $bnm = trim((string) ($addr['bnm'] ?? $addr['building_name'] ?? ''));
            $st = trim((string) ($addr['st'] ?? $addr['street'] ?? $addr['street_name'] ?? ''));
            $loc = trim((string) ($addr['loc'] ?? $addr['location'] ?? $addr['locality'] ?? ''));
            $city = trim((string) ($addr['city'] ?? $addr['city_name'] ?? ''));
            $district = trim((string) ($addr['dst'] ?? $addr['district'] ?? ''));
            $pincode = trim((string) ($addr['pncd'] ?? $addr['pincode'] ?? $addr['postal_code'] ?? $addr['pin_code'] ?? ''));
            $stateRaw = trim((string) ($addr['stcd'] ?? $addr['state'] ?? $addr['state_name'] ?? ''));

            if ($city === '' && $district !== '') {
                $city = $district;
            }

            $bldg = $bno;
            if ($flno !== '') {
                $bldg = $bno !== '' ? "{$flno}, {$bno}" : $flno;
            }

            $line1Parts = array_filter([$bldg, $bnm, $st], fn ($v) => $v !== '');
            $addressLine1 = implode(', ', $line1Parts);

            $line2Parts = array_filter([$loc, $district !== $city ? $district : ''], fn ($v) => $v !== '');
            $addressLine2 = implode(', ', $line2Parts);

            $fullParts = array_filter([$addressLine1, $addressLine2, $city, $pincode], fn ($v) => $v !== '');
            $fullAddress = implode(', ', $fullParts);
        } else {
            $fullAddress = trim((string) $addr);
            $addressLine1 = $fullAddress;
        }

        // State name resolution
        $state = '';
        if ($stateRaw !== '') {
            if (isset(IndianStates::all()[$stateRaw])) {
                $state = IndianStates::all()[$stateRaw];
            } else {
                foreach (IndianStates::all() as $nameVal) {
                    if (strcasecmp($nameVal, $stateRaw) === 0) {
                        $state = $nameVal;
                        break;
                    }
                }
            }
        }

        if ($state === '') {
            $state = IndianStates::stateFromGstin($gstin) ?? '';
        }

        return [
            'success' => true,
            'is_live' => true,
            'message' => 'GSTIN verified successfully from official records.',
            'party' => [
                'gstin' => $gstin,
                'name' => $name,
                'trade_name' => $tradeName,
                'legal_name' => $legalName,
                'pan' => $pan,
                'state' => $state,
                'city' => $city,
                'pincode' => $pincode,
                'address' => $fullAddress,
                'address_line_1' => $addressLine1,
                'address_line_2' => $addressLine2,
                'status' => $status,
                'registration_date' => $data['rgdt'] ?? $data['registration_date'] ?? null,
                'taxpayer_type' => $data['dty'] ?? $data['taxpayer_type'] ?? null,
            ],
        ];
    }

    protected function getRequiredCredentialsName(string $provider): string
    {
        return match ($provider) {
            'sandbox' => 'Sandbox API credentials (GST_API_KEY and GST_API_SECRET)',
            'cleartax' => 'ClearTax auth token (GST_API_KEY)',
            'mastersindia' => 'Masters India credentials (MASTERSINDIA_CLIENT_ID / SECRET)',
            'custom' => 'Custom GST API endpoint (GST_API_URL)',
            default => 'GST API credentials (GST_API_KEY)',
        };
    }

    protected function getRequiredConfigKeys(string $provider): array
    {
        return match ($provider) {
            'sandbox' => ['GST_API_KEY', 'GST_API_SECRET'],
            'cleartax' => ['GST_API_KEY'],
            'mastersindia' => ['MASTERSINDIA_CLIENT_ID', 'MASTERSINDIA_CLIENT_SECRET', 'MASTERSINDIA_USERNAME', 'MASTERSINDIA_PASSWORD'],
            'custom' => ['GST_API_URL', 'GST_API_KEY'],
            default => ['GST_API_KEY'],
        };
    }
}