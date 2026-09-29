<?php

namespace App\Domains\Master\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GstLookupService
{
    /**
     * Complete list of Indian States and Union Territories (28 States + 8 UTs).
     */
    public const STATES = [
        'Andhra Pradesh',
        'Arunachal Pradesh',
        'Assam',
        'Bihar',
        'Chhattisgarh',
        'Goa',
        'Gujarat',
        'Haryana',
        'Himachal Pradesh',
        'Jharkhand',
        'Karnataka',
        'Kerala',
        'Madhya Pradesh',
        'Maharashtra',
        'Manipur',
        'Meghalaya',
        'Mizoram',
        'Nagaland',
        'Odisha',
        'Punjab',
        'Rajasthan',
        'Sikkim',
        'Tamil Nadu',
        'Telangana',
        'Tripura',
        'Uttar Pradesh',
        'Uttarakhand',
        'West Bengal',
        'Andaman and Nicobar Islands',
        'Chandigarh',
        'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi',
        'Jammu and Kashmir',
        'Ladakh',
        'Lakshadweep',
        'Puducherry',
    ];

    /**
     * Map Indian GST State Codes (first 2 digits) to state names.
     */
    public const STATE_CODES = [
        '01' => 'Jammu and Kashmir',
        '02' => 'Himachal Pradesh',
        '03' => 'Punjab',
        '04' => 'Chandigarh',
        '05' => 'Uttarakhand',
        '06' => 'Haryana',
        '07' => 'Delhi',
        '08' => 'Rajasthan',
        '09' => 'Uttar Pradesh',
        '10' => 'Bihar',
        '11' => 'Sikkim',
        '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland',
        '14' => 'Manipur',
        '15' => 'Mizoram',
        '16' => 'Tripura',
        '17' => 'Meghalaya',
        '18' => 'Assam',
        '19' => 'West Bengal',
        '20' => 'Jharkhand',
        '21' => 'Odisha',
        '22' => 'Chhattisgarh',
        '23' => 'Madhya Pradesh',
        '24' => 'Gujarat',
        '26' => 'Dadra and Nagar Haveli and Daman and Diu',
        '27' => 'Maharashtra',
        '29' => 'Karnataka',
        '30' => 'Goa',
        '31' => 'Lakshadweep',
        '32' => 'Kerala',
        '33' => 'Tamil Nadu',
        '34' => 'Puducherry',
        '35' => 'Andaman and Nicobar Islands',
        '36' => 'Telangana',
        '37' => 'Andhra Pradesh',
        '38' => 'Ladakh',
        '97' => 'Other Territory',
    ];

    /**
     * Get the complete list of Indian states and UTs.
     *
     * @return string[]
     */
    public static function states(): array
    {
        return self::STATES;
    }

    /**
     * Look up GSTIN details from configured provider or developer fallback.
     *
     * @return array{
     *     gstin: string,
     *     legal_name: string,
     *     trade_name: string,
     *     name: string,
     *     status: string,
     *     registration_date: ?string,
     *     constitution: ?string,
     *     taxpayer_type: ?string,
     *     address: ?string,
     *     state: ?string,
     *     pincode: ?string,
     *     district: ?string
     * }
     */
    public function search(string $gstin): array
    {
        $gstin = strtoupper(trim($gstin));

        if (! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
            throw new RuntimeException('Please enter a valid 15-character GSTIN.');
        }

        $cacheKey = 'gst_lookup_' . md5($gstin);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($gstin) {
            $provider = config('services.gst_search.provider', 'mastersindia');

            if ($provider === 'custom' && filled(config('services.gst_search.api_url'))) {
                return $this->lookupCustomProvider($gstin);
            }

            if ($this->isMastersIndiaConfigured()) {
                return $this->lookupMastersIndia($gstin);
            }

            if ((bool) config('services.mastersindia.fallback_to_stub', true)) {
                return $this->stubTaxpayerDetails($gstin);
            }

            throw new RuntimeException('GST Search API credentials are not configured.');
        });
    }

    protected function isMastersIndiaConfigured(): bool
    {
        return filled(config('services.mastersindia.client_id'))
            && filled(config('services.mastersindia.client_secret'))
            && filled(config('services.mastersindia.username'))
            && filled(config('services.mastersindia.password'));
    }

    protected function lookupMastersIndia(string $gstin): array
    {
        $baseUrl = rtrim((string) config('services.mastersindia.base_url'), '/');
        $timeout = (int) config('services.mastersindia.timeout', 15);
        $token = $this->getMastersIndiaToken();

        try {
            $response = Http::baseUrl($baseUrl)
                ->timeout($timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'client_id' => (string) config('services.mastersindia.client_id'),
                    'client_secret' => (string) config('services.mastersindia.client_secret'),
                ])
                ->get('/commonapi/v1.1/search', [
                    'gstin' => $gstin,
                ]);

            if (! $response->ok()) {
                Log::warning('MastersIndia GST search failed', ['status' => $response->status(), 'body' => $response->body()]);

                if ($response->status() === 404) {
                    throw new RuntimeException('GST details not found for the provided GSTIN.');
                }

                if ((bool) config('services.mastersindia.fallback_to_stub', true)) {
                    return $this->stubTaxpayerDetails($gstin);
                }

                throw new RuntimeException('GST service returned an error. Please try again.');
            }

            $json = $response->json();
            $data = $json['data'] ?? $json['Data'] ?? $json;

            return $this->normalizeTaxpayerData($gstin, $data);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MastersIndia GST search exception', ['error' => $e->getMessage()]);

            if ((bool) config('services.mastersindia.fallback_to_stub', true)) {
                return $this->stubTaxpayerDetails($gstin);
            }

            throw new RuntimeException('Unable to communicate with the GST Search Service. Please try again.');
        }
    }

    protected function lookupCustomProvider(string $gstin): array
    {
        $url = config('services.gst_search.api_url');
        $apiKey = config('services.gst_search.api_key');
        $timeout = (int) config('services.gst_search.timeout', 15);

        try {
            $req = Http::timeout($timeout)->acceptJson();
            if (filled($apiKey)) {
                $req = $req->withHeaders(['x-api-key' => $apiKey, 'Authorization' => 'Bearer ' . $apiKey]);
            }

            $response = $req->get(str_replace('{gstin}', $gstin, $url), ['gstin' => $gstin]);

            if (! $response->ok()) {
                if ($response->status() === 404) {
                    throw new RuntimeException('GST details not found for the provided GSTIN.');
                }
                throw new RuntimeException('GST lookup provider error: ' . $response->status());
            }

            $json = $response->json();
            $data = $json['data'] ?? $json['result'] ?? $json;

            return $this->normalizeTaxpayerData($gstin, $data);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Custom GST search exception', ['error' => $e->getMessage()]);
            throw new RuntimeException('Unable to connect to GST provider.');
        }
    }

    protected function getMastersIndiaToken(): string
    {
        $clientId = (string) config('services.mastersindia.client_id');
        $key = 'mastersindia:gst_token:' . md5($clientId);

        return Cache::remember($key, now()->addMinutes(50), function () {
            $baseUrl = rtrim((string) config('services.mastersindia.base_url'), '/');
            $timeout = (int) config('services.mastersindia.timeout', 15);

            $resp = Http::baseUrl($baseUrl)
                ->timeout($timeout)
                ->asJson()
                ->post('/oauth/token', [
                    'username' => config('services.mastersindia.username'),
                    'password' => config('services.mastersindia.password'),
                    'client_id' => config('services.mastersindia.client_id'),
                    'client_secret' => config('services.mastersindia.client_secret'),
                    'grant_type' => 'password',
                ]);

            if (! $resp->ok()) {
                throw new RuntimeException('Masters India authentication failed.');
            }

            return (string) ($resp->json('access_token') ?? $resp->json('data.access_token') ?? '');
        });
    }

    /**
     * Normalize raw GST data from standard GSTN / Masters India formats.
     */
    protected function normalizeTaxpayerData(string $gstin, array $data): array
    {
        $legalName = trim((string) ($data['lgnm'] ?? $data['legal_name'] ?? $data['legalName'] ?? ''));
        $tradeName = trim((string) ($data['tradeNam'] ?? $data['trade_name'] ?? $data['tradeName'] ?? ''));
        $status = ucfirst(strtolower((string) ($data['sts'] ?? $data['status'] ?? 'Active')));
        $regDate = (string) ($data['rgdt'] ?? $data['registration_date'] ?? '');
        $constitution = (string) ($data['ctb'] ?? $data['constitution'] ?? '');
        $taxpayerType = (string) ($data['dty'] ?? $data['taxpayer_type'] ?? '');

        // Extract primary address
        $pradr = $data['pradr'] ?? $data['principal_address'] ?? $data['address'] ?? [];
        $addrObj = is_array($pradr) ? ($pradr['addr'] ?? $pradr) : [];

        $addressParts = [];
        if (is_array($addrObj)) {
            foreach (['flno', 'bno', 'bnm', 'st', 'loc', 'dst', 'stcd', 'pncd'] as $key) {
                if (filled($addrObj[$key] ?? null)) {
                    $addressParts[] = trim((string) $addrObj[$key]);
                }
            }
        }

        $fullAddress = ! empty($addressParts)
            ? implode(', ', array_unique($addressParts))
            : (is_string($pradr) ? $pradr : '');

        $pincode = (string) ($addrObj['pncd'] ?? $data['pincode'] ?? '');
        $stateCode = substr($gstin, 0, 2);
        $state = (string) ($addrObj['stcd'] ?? self::STATE_CODES[$stateCode] ?? '');
        $district = (string) ($addrObj['dst'] ?? $data['district'] ?? '');

        // Preferred display name: Trade Name, falling back to Legal Name
        $displayName = $tradeName !== '' ? $tradeName : $legalName;

        return [
            'gstin' => $gstin,
            'legal_name' => $legalName,
            'trade_name' => $tradeName,
            'name' => $displayName,
            'status' => $status ?: 'Active',
            'registration_date' => $regDate ?: null,
            'constitution' => $constitution ?: null,
            'taxpayer_type' => $taxpayerType ?: null,
            'address' => $fullAddress ?: null,
            'state' => $state ?: (self::STATE_CODES[$stateCode] ?? null),
            'pincode' => $pincode ?: null,
            'district' => $district ?: null,
        ];
    }

    /**
     * Deterministic development stub for offline / testing mode.
     */
    protected function stubTaxpayerDetails(string $gstin): array
    {
        $stateCode = substr($gstin, 0, 2);
        $state = self::STATE_CODES[$stateCode] ?? 'Maharashtra';
        $pan = substr($gstin, 2, 10);

        return [
            'gstin' => $gstin,
            'legal_name' => 'ENTERPRISE ' . $pan . ' PRIVATE LIMITED',
            'trade_name' => 'ENTERPRISE TRADERS (' . $state . ')',
            'name' => 'ENTERPRISE TRADERS (' . $state . ')',
            'status' => 'Active',
            'registration_date' => '01/07/2017',
            'constitution' => 'Private Limited Company',
            'taxpayer_type' => 'Regular',
            'address' => 'Plot No. 42, Commercial Complex, Main Road, ' . $state,
            'state' => $state,
            'pincode' => $stateCode . '0001',
            'district' => $state,
        ];
    }
}