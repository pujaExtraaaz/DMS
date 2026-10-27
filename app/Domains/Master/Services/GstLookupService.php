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
     * Validate GSTIN check-digit (mod-36 algorithm used by GSTN / Sandbox).
     */
    public static function hasValidChecksum(?string $gstin): bool
    {
        if (! self::isValidGstin($gstin)) {
            return false;
        }

        $gstin = strtoupper(trim((string) $gstin));
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $factor = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2];
        $sum = 0;

        for ($i = 0; $i < 14; $i++) {
            $code = strpos($chars, $gstin[$i]);
            if ($code === false) {
                return false;
            }
            $product = $code * $factor[$i];
            $sum += intdiv($product, 36) + ($product % 36);
        }

        $checkCode = (36 - ($sum % 36)) % 36;

        return $gstin[14] === $chars[$checkCode];
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
     *     party?: array<string, mixed>,
     *     required_keys?: array<int, string>
     * }
     */
    public function search(string $gstin): array
    {
        $gstin = strtoupper(trim($gstin));

        if (strlen($gstin) !== 15) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Please enter a valid 15-character GSTIN (e.g. 27AAAAA0000A1Z2).',
            ];
        }

        if (! self::isValidGstin($gstin)) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Invalid GSTIN format. A valid GSTIN consists of 2-digit state code, 10-character PAN, 1 entity digit, and checksum.',
            ];
        }

        $stateCode = substr($gstin, 0, 2);
        if (! isset(IndianStates::all()[$stateCode])) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => "Invalid GSTIN state code '{$stateCode}'. State code must be between 01 and 38 (or 97).",
            ];
        }

        if (! self::hasValidChecksum($gstin)) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Invalid GSTIN checksum. Please verify the GSTIN and try again.',
                'extracted' => $this->extractedFromGstin($gstin),
            ];
        }

        $provider = strtolower((string) config('services.gst.provider', 'sandbox'));

        if (! $this->isConfigured($provider)) {
            return [
                'success' => false,
                'is_configured' => false,
                'status_code' => 503,
                'message' => "GST verification API is not configured. Please configure {$this->getRequiredCredentialsName($provider)} in your environment.",
                'provider' => $provider,
                'required_keys' => $this->getRequiredConfigKeys($provider),
                'extracted' => $this->extractedFromGstin($gstin),
            ];
        }

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
                'extracted' => $this->extractedFromGstin($gstin),
            ];
        } catch (ConnectionException $e) {
            Log::warning('GST API connection timeout for GSTIN '.$gstin.': '.$e->getMessage());

            return [
                'success' => false,
                'status_code' => 504,
                'message' => 'GST verification service timed out. Please try again or enter party details manually.',
                'extracted' => $this->extractedFromGstin($gstin),
            ];
        } catch (\Throwable $e) {
            Log::warning('GST API unexpected error for GSTIN '.$gstin.': '.$e->getMessage());

            return [
                'success' => false,
                'status_code' => 502,
                'message' => 'Unable to connect to GST verification provider. Please try again later or enter details manually.',
                'extracted' => $this->extractedFromGstin($gstin),
            ];
        }

        return $this->handleProviderResponse($response, $gstin, $provider);
    }

    /**
     * PAN / state derived from GSTIN structure (not provider-verified).
     *
     * @return array{pan: string, state: string, state_code: string}
     */
    protected function extractedFromGstin(string $gstin): array
    {
        return [
            'pan' => substr($gstin, 2, 10),
            'state' => IndianStates::stateFromGstin($gstin) ?? '',
            'state_code' => substr($gstin, 0, 2),
        ];
    }

    /**
     * Obtain or retrieve cached Sandbox access token.
     */
    protected function getSandboxAccessToken(string $baseUrl): string
    {
        $apiKey = (string) config('services.gst.api_key');
        $apiSecret = (string) config('services.gst.api_secret');

        if (str_starts_with($apiKey, 'eyJ')) {
            return $apiKey;
        }
        if (str_starts_with($apiSecret, 'eyJ')) {
            return $apiSecret;
        }

        if (blank($apiSecret)) {
            return $apiKey;
        }

        $cacheKey = 'sandbox:gst_lookup_token:'.md5($apiKey.$apiSecret);

        return Cache::remember($cacheKey, now()->addHours(23), function () use ($baseUrl, $apiKey, $apiSecret) {
            $timeout = (int) config('services.gst.timeout', 15);
            $authResponse = Http::timeout($timeout)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'x-api-secret' => $apiSecret,
                    'x-api-version' => '1.0',
                    'Accept' => 'application/json',
                ])
                ->post("{$baseUrl}/authenticate");

            if ($authResponse->status() === 401 || $authResponse->status() === 403) {
                Log::warning("Sandbox API authentication failure at /authenticate: HTTP {$authResponse->status()}");
                throw new \InvalidArgumentException('GST verification service authentication failed. Please check provider API credentials.');
            }

            if (! $authResponse->successful()) {
                Log::warning("Sandbox API authentication error: HTTP {$authResponse->status()}");
                throw new \RuntimeException('Sandbox authentication failed: '.($this->safeProviderMessage($authResponse) ?? "HTTP {$authResponse->status()}"));
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
            'x-api-version' => '1.0',
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

        if ($response->status() === 404 && filled($customUrl) && str_contains($customUrl, '/gsp/')) {
            $fallbackEndpoint = "{$baseUrl}/gsp/public/gstin/{$gstin}";

            return Http::timeout($timeout)->withHeaders($headers)->get($fallbackEndpoint);
        }

        return $response;
    }

    protected function callClearTax(string $gstin): Response
    {
        $configuredUrl = (string) config('services.gst.api_url');
        // Ignore Sandbox base URL when ClearTax provider is selected.
        $baseUrl = rtrim(
            (filled($configuredUrl) && ! str_contains($configuredUrl, 'sandbox.co.in'))
                ? $configuredUrl
                : 'https://api.cleartax.in',
            '/'
        );
        $endpoint = "{$baseUrl}/enterprise/v1/gstin/{$gstin}";
        $timeout = (int) config('services.gst.timeout', 15);

        return Http::timeout($timeout)
            ->withHeaders([
                'x-cleartax-auth-token' => (string) config('services.gst.api_key'),
                'Accept' => 'application/json',
            ])
            ->get($endpoint);
    }

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

    protected function getMastersIndiaOauthToken(string $baseUrl): string
    {
        $clientId = (string) config('services.mastersindia.client_id');
        $cacheKey = 'mastersindia:gst_lookup_token:'.md5($clientId);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && filled($cached)) {
            return $cached;
        }

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
            throw new \RuntimeException('Masters India authentication failed: '.$resp->status());
        }

        $token = (string) ($resp->json('access_token') ?? $resp->json('data.access_token') ?? '');
        if (blank($token)) {
            throw new \RuntimeException('Masters India authentication failed: no access token returned.');
        }

        Cache::put($cacheKey, $token, now()->addMinutes(50));

        return $token;
    }

    /**
     * Process HTTP response and return clean, normalized party details.
     */
    protected function handleProviderResponse(Response $response, string $gstin, string $provider): array
    {
        $status = $response->status();
        $extracted = $this->extractedFromGstin($gstin);

        if ($status === 404) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => "GSTIN {$gstin} was not found or is not registered.",
                'extracted' => $extracted,
            ];
        }

        if ($status === 401 || $status === 403) {
            Log::warning("GST API auth failure for provider [{$provider}]: HTTP {$status}");

            return [
                'success' => false,
                'status_code' => 401,
                'message' => 'GST verification service authentication failed. Please check provider API credentials.',
                'extracted' => $extracted,
            ];
        }

        if ($status === 429) {
            return [
                'success' => false,
                'status_code' => 429,
                'message' => 'GST verification API rate limit reached. Please wait a moment and try again.',
                'extracted' => $extracted,
            ];
        }

        if ($status === 400) {
            $providerMessage = $this->safeProviderMessage($response) ?? 'The GST provider rejected this GSTIN.';

            return [
                'success' => false,
                'status_code' => 400,
                'message' => $providerMessage,
                'extracted' => $extracted,
            ];
        }

        if ($response->serverError()) {
            Log::warning("GST API server error for provider [{$provider}]: HTTP {$status}");

            return [
                'success' => false,
                'status_code' => $status,
                'message' => 'GST verification service is temporarily unavailable. Please enter details manually or retry later.',
                'extracted' => $extracted,
            ];
        }

        if (! $response->successful()) {
            $providerMessage = $this->safeProviderMessage($response);

            return [
                'success' => false,
                'status_code' => $status,
                'message' => $providerMessage
                    ? $providerMessage
                    : "GST lookup failed with status {$status}. Please enter details manually.",
                'extracted' => $extracted,
            ];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return [
                'success' => false,
                'status_code' => 502,
                'message' => 'Invalid response received from GST lookup provider.',
                'extracted' => $extracted,
            ];
        }

        if ($notFound = $this->detectNotFoundResponse($json, $gstin)) {
            $notFound['extracted'] = $extracted;

            return $notFound;
        }

        $data = $this->extractTaxpayerPayload($json);

        if (! is_array($data) || ! $this->payloadHasIdentity($data)) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => "GSTIN {$gstin} was not found or is not registered.",
                'extracted' => $extracted,
            ];
        }

        $normalized = $this->normalizeTaxpayerData($data, $gstin);

        if (empty($normalized['success'])) {
            $normalized['extracted'] = $extracted;

            return $normalized;
        }

        return $normalized;
    }

    /**
     * Extract a safe, non-sensitive provider message for the UI.
     */
    protected function safeProviderMessage(Response $response): ?string
    {
        $json = $response->json();
        if (! is_array($json)) {
            return null;
        }

        $candidates = [
            $json['message'] ?? null,
            data_get($json, 'error.message'),
            is_string($json['error'] ?? null) ? $json['error'] : null,
            data_get($json, 'data.message'),
            data_get($json, 'data.error.message'),
            $json['status_desc'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }
            $message = trim($candidate);
            if ($message === '') {
                continue;
            }
            // Never echo tokens / secrets if a provider mistakenly includes them.
            if (preg_match('/eyJ[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+/i', $message)) {
                continue;
            }
            if (preg_match('/(api[_-]?key|api[_-]?secret|authorization|bearer\s+)/i', $message)) {
                continue;
            }

            return $message;
        }

        return null;
    }

    /**
     * Detect FO8000 / status_cd=0 / explicit not-found payloads that still return HTTP 200.
     *
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>|null
     */
    protected function detectNotFoundResponse(array $json, string $gstin): ?array
    {
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

        $errorCd = (string) (
            data_get($json, 'data.error.error_cd')
            ?? data_get($json, 'data.error_cd')
            ?? data_get($json, 'error.error_cd')
            ?? ''
        );

        $errorMessage = (string) (
            data_get($json, 'data.error.message')
            ?? data_get($json, 'data.message')
            ?? data_get($json, 'error.message')
            ?? ($json['message'] ?? '')
        );

        if ($errorCd === 'FO8000' || stripos($errorMessage, 'no records') !== false) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => $errorMessage !== '' ? $errorMessage : "GSTIN {$gstin} was not found or is not registered.",
            ];
        }

        if (isset($json['error']) && ! empty($json['error']) && ! $this->payloadHasIdentity($this->extractTaxpayerPayload($json) ?? [])) {
            $msg = is_array($json['error'])
                ? (string) ($json['error']['message'] ?? "GSTIN {$gstin} lookup failed.")
                : (string) $json['error'];

            return [
                'success' => false,
                'status_code' => 404,
                'message' => $msg,
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>|null
     */
    protected function extractTaxpayerPayload(array $json): ?array
    {
        $data = $json['data'] ?? $json['taxpayerInfo'] ?? $json['taxpayer_info'] ?? $json;

        if (! is_array($data)) {
            return null;
        }

        // Sandbox wraps GSTN payload at data.data
        if (isset($data['data']) && is_array($data['data']) && $this->payloadHasIdentity($data['data'])) {
            return $data['data'];
        }

        if (isset($data[0]) && is_array($data[0]) && $this->payloadHasIdentity($data[0])) {
            return $data[0];
        }

        if ($this->payloadHasIdentity($data)) {
            return $data;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    protected function findLabeledString(array $data, array $keys, int $depth = 0): string
    {
        $wanted = array_map('strtolower', $keys);
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $wanted, true) && is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }
        if ($depth >= 4) {
            return '';
        }
        foreach ($data as $value) {
            if (! is_array($value)) {
                continue;
            }
            $found = $this->findLabeledString($value, $keys, $depth + 1);
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }
    protected function payloadHasIdentity(array $data): bool
    {
        foreach (['tradeNam', 'lgnm', 'trade_name', 'legal_name', 'tradeName', 'legalName'] as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize raw taxpayer data to standard DMS party fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeTaxpayerData(array $data, string $gstin): array
    {
        $tradeName = $this->findLabeledString($data, ['tradeNam', 'trade_name', 'tradeName', 'trade_nm', 'trdnm', 'tradename']);
        $legalName = $this->findLabeledString($data, ['lgnm', 'legal_name', 'legalName', 'legal_nm', 'legalname']);

        // Prefer legal business name; keep trade name available for review.
        $name = $legalName !== '' ? $legalName : $tradeName;
        if ($name === '') {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => "GSTIN {$gstin} was found but no business name was returned.",
            ];
        }

        $returnedGstin = strtoupper(trim((string) ($data['gstin'] ?? $data['GSTIN'] ?? $gstin)));
        if ($returnedGstin !== '' && $returnedGstin !== $gstin) {
            Log::warning("GST lookup GSTIN mismatch: requested {$gstin}, provider returned {$returnedGstin}");
        }

        $pan = trim((string) ($data['pan'] ?? ''));
        if ($pan === '' || strlen($pan) !== 10) {
            $pan = substr($gstin, 2, 10);
        }

        $status = trim((string) ($data['sts'] ?? $data['status'] ?? $data['gstin_status'] ?? ''));
        if ($status === '') {
            $status = 'Active';
        }

        $principal = $this->normalizeAddressBlock(
            $data['pradr']['addr']
                ?? $data['principal_place_of_business']['address']
                ?? $data['principal_place_of_business']
                ?? $data['address']
                ?? [],
            $gstin,
            'Principal Place of Business'
        );

        $additionalAddresses = [];
        $adadr = $data['adadr'] ?? $data['additional_places_of_business'] ?? [];
        if (is_array($adadr)) {
            foreach ($adadr as $index => $block) {
                $addr = is_array($block) ? ($block['addr'] ?? $block['address'] ?? $block) : [];
                $normalized = $this->normalizeAddressBlock($addr, $gstin, 'Additional Place of Business '.((int) $index + 1));
                if ($normalized['address_line_1'] === '' && $normalized['city'] === '' && $normalized['pincode'] === '') {
                    continue;
                }
                if ($this->addressesMatch($principal, $normalized)) {
                    continue;
                }
                $nature = $block['ntr'] ?? null;
                if (is_array($nature)) {
                    $normalized['nature_of_business'] = array_values(array_filter(array_map('strval', $nature)));
                } elseif (is_string($nature) && trim($nature) !== '') {
                    $normalized['nature_of_business'] = [trim($nature)];
                }
                $additionalAddresses[] = $normalized;
            }
        }

        $nba = $data['nba'] ?? $data['nature_of_business'] ?? null;
        $natureOfBusiness = [];
        if (is_array($nba)) {
            $natureOfBusiness = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $nba)));
        } elseif (is_string($nba) && trim($nba) !== '') {
            $natureOfBusiness = [trim($nba)];
        }

        $pradrNature = $data['pradr']['ntr'] ?? null;
        if ($natureOfBusiness === []) {
            if (is_array($pradrNature)) {
                $natureOfBusiness = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $pradrNature)));
            } elseif (is_string($pradrNature) && trim($pradrNature) !== '') {
                $natureOfBusiness = [trim($pradrNature)];
            }
        }

        $state = $principal['state'] !== '' ? $principal['state'] : (IndianStates::stateFromGstin($gstin) ?? '');
        $stateCode = $principal['state_code'] !== '' ? $principal['state_code'] : substr($gstin, 0, 2);

        $registrationDate = $this->nullableString($data['rgdt'] ?? $data['registration_date'] ?? $data['registrationDate'] ?? null);
        $cancellationDate = $this->nullableString($data['cxdt'] ?? $data['canclDt'] ?? $data['cancellation_date'] ?? null);
        $constitution = $this->nullableString($data['ctb'] ?? $data['constitution'] ?? $data['constitution_of_business'] ?? null);
        $taxpayerType = $this->nullableString($data['dty'] ?? $data['taxpayer_type'] ?? $data['taxpayerType'] ?? null);
        $stateJurisdiction = $this->nullableString($data['stj'] ?? $data['state_jurisdiction'] ?? null);
        $centerJurisdiction = $this->nullableString($data['ctj'] ?? $data['center_jurisdiction'] ?? null);
        $einvoiceStatus = $this->nullableString($data['einvoiceStatus'] ?? $data['einvoice_status'] ?? null);

        $isInactive = $status !== '' && ! in_array(strtolower($status), ['active', 'provisional'], true);

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
                'state_code' => $stateCode,
                'city' => $principal['city'],
                'district' => $principal['district'],
                'pincode' => $principal['pincode'],
                'address' => $principal['address'],
                'address_line_1' => $principal['address_line_1'],
                'address_line_2' => $principal['address_line_2'],
                'address_label' => $principal['label'],
                'country' => $principal['country'],
                'status' => $status,
                'registration_date' => $registrationDate,
                'cancellation_date' => $cancellationDate,
                'constitution_of_business' => $constitution,
                'taxpayer_type' => $taxpayerType,
                'nature_of_business' => $natureOfBusiness,
                'state_jurisdiction' => $stateJurisdiction,
                'center_jurisdiction' => $centerJurisdiction,
                'einvoice_status' => $einvoiceStatus,
                'is_inactive' => $isInactive,
                'addresses' => array_values(array_filter(array_merge(
                    [$principal['address_line_1'] !== '' || $principal['city'] !== '' || $principal['pincode'] !== '' ? $principal : null],
                    $additionalAddresses
                ))),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|string  $addr
     * @return array{
     *     label: string,
     *     address_line_1: string,
     *     address_line_2: string,
     *     city: string,
     *     district: string,
     *     state: string,
     *     state_code: string,
     *     pincode: string,
     *     country: string,
     *     address: string,
     *     building: string,
     *     street: string,
     *     locality: string
     * }
     */
    protected function normalizeAddressBlock(array|string $addr, string $gstin, string $label): array
    {
        $empty = [
            'label' => $label,
            'address_line_1' => '',
            'address_line_2' => '',
            'city' => '',
            'district' => '',
            'state' => '',
            'state_code' => '',
            'pincode' => '',
            'country' => '',
            'address' => '',
            'building' => '',
            'street' => '',
            'locality' => '',
        ];

        if (! is_array($addr)) {
            $full = trim((string) $addr);
            if ($full === '') {
                return $empty;
            }

            return array_merge($empty, [
                'address_line_1' => $full,
                'address' => $full,
                'state' => IndianStates::stateFromGstin($gstin) ?? '',
                'state_code' => substr($gstin, 0, 2),
            ]);
        }

        $flno = trim((string) ($addr['flno'] ?? $addr['floor_number'] ?? $addr['floor_no'] ?? ''));
        $bno = trim((string) ($addr['bno'] ?? $addr['building_number'] ?? $addr['building_no'] ?? $addr['door_no'] ?? ''));
        $bnm = trim((string) ($addr['bnm'] ?? $addr['building_name'] ?? ''));
        $st = trim((string) ($addr['st'] ?? $addr['street'] ?? $addr['street_name'] ?? ''));
        $loc = trim((string) ($addr['loc'] ?? $addr['location'] ?? ''));
        $locality = trim((string) ($addr['locality'] ?? $addr['locality_name'] ?? ''));
        $landmark = trim((string) ($addr['landMark'] ?? $addr['landmark'] ?? ''));
        $city = trim((string) ($addr['city'] ?? $addr['city_name'] ?? $addr['town'] ?? ''));
        $district = trim((string) ($addr['dst'] ?? $addr['district'] ?? ''));
        $pincode = trim((string) ($addr['pncd'] ?? $addr['pincode'] ?? $addr['postal_code'] ?? $addr['pin_code'] ?? ''));
        $stateRaw = trim((string) ($addr['stcd'] ?? $addr['state'] ?? $addr['state_name'] ?? ''));
        $country = trim((string) ($addr['country'] ?? $addr['cntry'] ?? ''));

        // GST "loc" is often a locality/landmark, not the city. Prefer explicit city, then district.
        if ($city === '' && $district !== '') {
            $city = $district;
        }
        if ($city === '' && $loc !== '' && ! $this->looksLikeLandmark($loc)) {
            $city = $loc;
        }
        if ($locality === '' && $loc !== '' && strcasecmp($loc, $city) !== 0) {
            $locality = $loc;
        }

        $floorBuilding = trim(implode(', ', array_filter([$flno, $bno], fn ($v) => $v !== '')));
        $building = trim(implode(', ', array_filter([$floorBuilding, $bnm], fn ($v) => $v !== '')));

        $line1Parts = array_filter([$building, $st], fn ($v) => $v !== '');
        $addressLine1 = implode(', ', $line1Parts);

        $line2Parts = [];
        foreach ([$locality !== '' ? $locality : null, $loc !== '' && $loc !== $city ? $loc : null, $landmark, $district !== '' && $district !== $city ? $district : null] as $part) {
            if (is_string($part) && $part !== '' && ! in_array($part, $line2Parts, true)) {
                $line2Parts[] = $part;
            }
        }
        $addressLine2 = implode(', ', $line2Parts);

        // If provider only returned a single combined street string, keep it on line 1.
        if ($addressLine1 === '' && $addressLine2 !== '') {
            $addressLine1 = $addressLine2;
            $addressLine2 = '';
        }

        [$state, $stateCode] = $this->resolveState($stateRaw, $gstin);

        $fullParts = array_filter([$addressLine1, $addressLine2, $city, $state, $pincode], fn ($v) => $v !== '');
        $fullAddress = implode(', ', $fullParts);

        return [
            'label' => $label,
            'address_line_1' => $addressLine1,
            'address_line_2' => $addressLine2,
            'city' => $city,
            'district' => $district,
            'state' => $state,
            'state_code' => $stateCode,
            'pincode' => $pincode,
            'country' => $country,
            'address' => $fullAddress,
            'building' => $building,
            'street' => $st,
            'locality' => $locality !== '' ? $locality : $loc,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveState(string $stateRaw, string $gstin): array
    {
        $states = IndianStates::all();
        $fallbackCode = substr($gstin, 0, 2);
        $fallbackName = $states[$fallbackCode] ?? '';

        if ($stateRaw === '') {
            return [$fallbackName, $fallbackCode];
        }

        if (isset($states[$stateRaw])) {
            return [$states[$stateRaw], $stateRaw];
        }

        foreach ($states as $code => $name) {
            if (strcasecmp($name, $stateRaw) === 0) {
                return [$name, (string) $code];
            }
        }

        return [$stateRaw, $fallbackCode];
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    protected function addressesMatch(array $a, array $b): bool
    {
        $normalize = static function (?string $value): string {
            return strtolower(preg_replace('/\s+/', ' ', trim((string) $value)) ?? '');
        };

        return $normalize($a['address_line_1'] ?? '') === $normalize($b['address_line_1'] ?? '')
            && $normalize($a['city'] ?? '') === $normalize($b['city'] ?? '')
            && $normalize($a['pincode'] ?? '') === $normalize($b['pincode'] ?? '');
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * Heuristic: GST location strings like "OPP. XYZ" / "NEAR ABC" are landmarks, not cities.
     */
    protected function looksLikeLandmark(string $value): bool
    {
        return (bool) preg_match('/\b(opp\.?|opposite|near|behind|beside|next to|shop|floor|building|apartment|plot)\b/i', $value);
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
