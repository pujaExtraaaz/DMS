<?php

namespace Tally\Tax;

use Illuminate\Support\Facades\Http;
use Throwable;

final class Gstin
{
    /**
     * @return array{
     *     gstin: string,
     *     state: string,
     *     state_code: string,
     *     pan: string,
     *     registration: string,
     *     message: string,
     *     legal_name: ?string,
     *     trade_name: ?string,
     *     name: ?string,
     *     address: ?string,
     *     billing_address: ?string,
     *     shipping_address: ?string,
     *     city: ?string,
     *     pincode: ?string,
     *     status: ?string
     * }|null
     */
    public static function lookup(string $value): ?array
    {
        $gstin = strtoupper(preg_replace('/\s+/', '', $value) ?? '');

        if (! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin)) {
            return null;
        }

        if (substr($gstin, 14, 1) !== self::checkCharacter(substr($gstin, 0, 14))) {
            return null;
        }

        $code = substr($gstin, 0, 2);
        $state = self::STATES[$code] ?? null;

        if ($state === null) {
            return null;
        }

        $pan = substr($gstin, 2, 10);
        $profile = self::taxpayer($gstin);
        $legal = $profile['legal_name'] ?? null;
        $trade = $profile['trade_name'] ?? null;
        $name = $trade ?: $legal;
        $address = $profile['address'] ?? null;
        $pincode = $profile['pincode'] ?? null;
        $city = $address === null ? null : self::city($address, $state, $pincode);
        $status = $profile['status'] ?? null;
        $registration = $profile['registration'] ?? 'regular';

        $message = $name
            ? $name.' is on the GST register for '.$state.'. PAN '.$pan.'.'
            : 'GSTIN is valid for '.$state.'. PAN '.$pan.'. The register did not return a name or address.';

        if ($status !== null && ! str_contains(strtolower($status), 'active')) {
            $message .= ' Status: '.$status.'.';
        }

        return [
            'gstin' => $gstin,
            'state' => $state,
            'state_code' => $code,
            'pan' => $pan,
            'registration' => $registration,
            'message' => $message,
            'legal_name' => $legal,
            'trade_name' => $trade,
            'name' => $name,
            'address' => $address,
            'billing_address' => $address,
            'shipping_address' => $address,
            'city' => $city,
            'pincode' => $pincode,
            'status' => $status,
        ];
    }

    /**
     * @return array{legal_name: ?string, trade_name: ?string, address: ?string, pincode: ?string, status: ?string, registration: ?string}|null
     */
    private static function taxpayer(string $gstin): ?array
    {
        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->withUserAgent('TallyWeb')
                ->get('https://gst.jamku.app/api/gstin/'.$gstin);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            return null;
        }

        $legal = self::text($data['lgnm'] ?? null);
        $trade = self::text($data['tradeName'] ?? $data['tradeNam'] ?? null);
        $address = self::text($data['adr'] ?? null);
        $pincode = self::text($data['pincode'] ?? null);
        $status = self::text($data['sts'] ?? null);
        $kind = strtolower((string) ($data['dty'] ?? ''));

        if ($pincode !== null && ! preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
            $pincode = null;
        }

        if ($legal === null && $trade === null && $address === null) {
            return null;
        }

        return [
            'legal_name' => $legal,
            'trade_name' => $trade,
            'address' => $address,
            'pincode' => $pincode,
            'status' => $status,
            'registration' => str_contains($kind, 'composition') ? 'composition' : 'regular',
        ];
    }

    private static function text(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private static function city(string $address, string $state, ?string $pincode): ?string
    {
        $parts = array_values(array_filter(array_map(trim(...), explode(',', $address)), fn (string $part): bool => $part !== ''));

        while ($parts !== []) {
            $last = $parts[array_key_last($parts)];

            if ($last === $pincode || strcasecmp($last, $state) === 0) {
                array_pop($parts);

                continue;
            }

            break;
        }

        $city = $parts === [] ? null : $parts[array_key_last($parts)];

        return $city === null || strcasecmp($city, $state) === 0 ? null : $city;
    }

    public static function checkCharacter(string $fourteen): string
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;
        $factor = 2;

        for ($index = strlen($fourteen) - 1; $index >= 0; $index--) {
            $code = strpos($alphabet, $fourteen[$index]);
            $product = ($code === false ? 0 : $code) * $factor;
            $sum += intdiv($product, 36) + ($product % 36);
            $factor = $factor === 2 ? 1 : 2;
        }

        return $alphabet[(36 - ($sum % 36)) % 36];
    }

    /**
     * Official GST state codes.
     *
     * @var array<string, string>
     */
    public const STATES = [
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
}
