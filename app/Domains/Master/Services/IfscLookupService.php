<?php

namespace App\Domains\Master\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IfscLookupService
{
    public const IFSC_REGEX = '/^[A-Z]{4}0[A-Z0-9]{6}$/';

    /**
     * Validate standard 11-character IFSC format.
     */
    public static function isValid(?string $ifsc): bool
    {
        if (! $ifsc) {
            return false;
        }

        $clean = strtoupper(trim($ifsc));

        return strlen($clean) === 11 && (bool) preg_match(self::IFSC_REGEX, $clean);
    }

    /**
     * Look up bank / branch details for an IFSC.
     *
     * Note: IFSC identifies a bank branch only. Account holder name, account
     * number, and account type cannot be resolved from IFSC alone.
     *
     * @return array{
     *     success: bool,
     *     status_code?: int,
     *     message: string,
     *     bank?: array{
     *         ifsc: string,
     *         bank_name: string,
     *         branch_name: string,
     *         address: string,
     *         city: string,
     *         state: string,
     *         micr: string,
     *         contact: string
     *     }
     * }
     */
    public function lookup(string $ifsc): array
    {
        $ifsc = strtoupper(trim($ifsc));

        if (strlen($ifsc) !== 11) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Please enter a valid 11-character IFSC code.',
            ];
        }

        if (! self::isValid($ifsc)) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Invalid IFSC format. Expected pattern: ABCD0123456 (4 letters, 0, then 6 alphanumeric).',
            ];
        }

        $cacheKey = 'ifsc_lookup:'.$ifsc;

        try {
            $payload = Cache::remember($cacheKey, now()->addDays(7), function () use ($ifsc) {
                return $this->fetchFromProvider($ifsc);
            });
        } catch (ConnectionException $e) {
            Log::warning('IFSC lookup connection timeout for '.$ifsc.': '.$e->getMessage());
            Cache::forget($cacheKey);

            return [
                'success' => false,
                'status_code' => 504,
                'message' => 'IFSC lookup timed out. Please try again or enter bank details manually.',
            ];
        } catch (\Throwable $e) {
            Log::warning('IFSC lookup failed for '.$ifsc.': '.$e->getMessage());
            Cache::forget($cacheKey);

            return [
                'success' => false,
                'status_code' => 502,
                'message' => $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Unable to look up this IFSC. Please enter bank details manually.',
            ];
        }

        if (empty($payload['success'])) {
            Cache::forget($cacheKey);

            return $payload;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchFromProvider(string $ifsc): array
    {
        $baseUrl = rtrim((string) config('services.ifsc.api_url', 'https://ifsc.razorpay.com'), '/');
        $timeout = (int) config('services.ifsc.timeout', 10);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->get("{$baseUrl}/{$ifsc}");

        if ($response->status() === 404) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => "IFSC {$ifsc} was not found. Please verify the code.",
            ];
        }

        if (! $response->successful()) {
            throw new \RuntimeException(
                'IFSC lookup service is temporarily unavailable (HTTP '.$response->status().').'
            );
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new \RuntimeException('Invalid response received from IFSC lookup provider.');
        }

        $bankName = trim((string) ($json['BANK'] ?? $json['bank'] ?? $json['bank_name'] ?? ''));
        $branchName = trim((string) ($json['BRANCH'] ?? $json['branch'] ?? $json['branch_name'] ?? ''));

        if ($bankName === '' && $branchName === '') {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => "IFSC {$ifsc} was not found or returned no bank details.",
            ];
        }

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Bank details fetched from IFSC.',
            'bank' => [
                'ifsc' => $ifsc,
                'bank_name' => $bankName,
                'branch_name' => $branchName,
                'address' => trim((string) ($json['ADDRESS'] ?? $json['address'] ?? '')),
                'city' => trim((string) ($json['CITY'] ?? $json['city'] ?? '')),
                'state' => trim((string) ($json['STATE'] ?? $json['state'] ?? '')),
                'micr' => trim((string) ($json['MICR'] ?? $json['micr'] ?? '')),
                'contact' => trim((string) ($json['CONTACT'] ?? $json['contact'] ?? '')),
            ],
        ];
    }
}
