<?php

namespace App\Domains\Hrms\Services\Essl;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class HttpEsslDriver implements EsslAttendanceDriver
{
    public function pullPunches(Carbon $from, Carbon $to): array
    {
        if (! config('hrms.essl.enabled')) {
            return [];
        }

        $url = rtrim((string) config('hrms.essl.url'), '/');
        if ($url === '') {
            throw new \RuntimeException('eSSL pull failed: ESSL_URL is empty. Set ESSL_URL or disable ESSL_ENABLED.');
        }

        $response = Http::timeout(30)
            ->withToken((string) config('hrms.essl.token'))
            ->get($url.'/punches', [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'company' => config('hrms.essl.company'),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('eSSL pull failed: HTTP '.$response->status());
        }

        $data = $response->json('punches') ?? $response->json() ?? [];

        return is_array($data) ? $data : [];
    }
}
