<?php

namespace App\Support;

class TitleCase
{
    /**
     * Common acronyms, abbreviations, and brand names that must preserve specific casing.
     */
    protected static array $acronymMap = [
        'gstin' => 'GSTIN',
        'gst' => 'GST',
        'pan' => 'PAN',
        'sku' => 'SKU',
        'hsn' => 'HSN',
        'od' => 'OD',
        'api' => 'API',
        'fy' => 'FY',
        'uom' => 'UOM',
        'uoms' => 'UOMs',
        'id' => 'ID',
        'ids' => 'IDs',
        'crm' => 'CRM',
        'hrms' => 'HRMS',
        'hr' => 'HR',
        'hq' => 'HQ',
        'po' => 'PO',
        'pi' => 'PI',
        'ar' => 'AR',
        'ap' => 'AP',
        'neft' => 'NEFT',
        'rtgs' => 'RTGS',
        'upi' => 'UPI',
        'itc' => 'ITC',
        'gstr' => 'GSTR',
        'gstr-1' => 'GSTR-1',
        'gstr-2b' => 'GSTR-2B',
        'gstr-3b' => 'GSTR-3B',
        'tds' => 'TDS',
        'tcs' => 'TCS',
        'kpi' => 'KPI',
        'kpis' => 'KPIs',
        'yoy' => 'YoY',
        'cvp' => 'CVP',
        'bs' => 'BS',
        'p&l' => 'P&L',
        'csv' => 'CSV',
        'pdf' => 'PDF',
        'json' => 'JSON',
        'url' => 'URL',
        'uuid' => 'UUID',
        'qr' => 'QR',
        'html' => 'HTML',
        'css' => 'CSS',
        'js' => 'JS',
        'erp' => 'ERP',
        'dms' => 'DMS',
        'lr' => 'LR',
        'lr/rr' => 'LR/RR',
        'tally' => 'Tally',
        'sms' => 'SMS',
        'otp' => 'OTP',
        'rfq' => 'RFQ',
        'rfqs' => 'RFQs',
        'e-way' => 'E-Way',
        'e-invoice' => 'E-Invoice',
        'oled' => 'OLED',
        'q1' => 'Q1',
        'q2' => 'Q2',
        'q3' => 'Q3',
        'q4' => 'Q4',
        'mrp' => 'MRP',
        'po/pi' => 'PO/PI',
        'hdmi' => 'HDMI',
        'usb' => 'USB',
        'led' => 'LED',
        'cpu' => 'CPU',
        'ram' => 'RAM',
        'ssd' => 'SSD',
        'hdd' => 'HDD',
        'ip' => 'IP',
        'lan' => 'LAN',
        'wan' => 'WAN',
        'wifi' => 'Wi-Fi',
        'wi-fi' => 'Wi-Fi',
        'vga' => 'VGA',
        'dvi' => 'DVI',
        'plc' => 'PLC',
        'hmi' => 'HMI',
        'vfd' => 'VFD',
        'mcb' => 'MCB',
        'midc' => 'MIDC',
        'mh' => 'MH',
        'dl' => 'DL',
        'ka' => 'KA',
        'tn' => 'TN',
        'gj' => 'GJ',
        'up' => 'UP',
        'mp' => 'MP',
        'wb' => 'WB',
        'rj' => 'RJ',
        'pb' => 'PB',
        'ga' => 'GA',
        'kl' => 'KL',
        'ap' => 'AP',
        'ts' => 'TS',
        'sap' => 'SAP',
        'mccb' => 'MCCB',
        'acb' => 'ACB',
        'rccb' => 'RCCB',
        'ups' => 'UPS',
        'ac' => 'AC',
        'dc' => 'DC',
        'pvc' => 'PVC',
        'tv' => 'TV',
        'tvs' => 'TVs',
        'nvme' => 'NVMe',
        'ddr' => 'DDR',
        'ddr2' => 'DDR2',
        'ddr3' => 'DDR3',
        'ddr4' => 'DDR4',
        'ddr5' => 'DDR5',
    ];

    /**
     * Format a string into Title Case while preserving special identifiers, acronyms, and HTML.
     */
    public static function format(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // Preserve raw URLs or email addresses untouched
        if (str_contains($text, '://') || (str_contains($text, '@') && !str_contains($text, ' '))) {
            return $text;
        }

        // If string contains HTML tags, format only the text chunks
        if (str_contains($text, '<') && str_contains($text, '>')) {
            $parts = preg_split('/(<[^>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
            $result = '';
            foreach ($parts as $part) {
                if (str_starts_with($part, '<') && str_ends_with($part, '>')) {
                    $result .= $part;
                } else {
                    $result .= static::formatTextSegment($part);
                }
            }
            return $result;
        }

        return static::formatTextSegment($text);
    }

    /**
     * Format a plain text segment into Title Case.
     */
    public static function formatTextSegment(string $text): string
    {
        // Normalize redundant internal whitespaces into single space
        $normalized = preg_replace('/[ \t]+/', ' ', trim($text));

        // Tokenize words, hyphens, slashes, and apostrophes
        return preg_replace_callback('/[A-Za-z0-9]+(?:[\'\/\-\&][A-Za-z0-9]+)*/u', function ($matches) {
            $token = $matches[0];
            $lower = mb_strtolower($token, 'UTF-8');

            // 1. Check known acronyms or terms
            if (isset(static::$acronymMap[$lower])) {
                return static::$acronymMap[$lower];
            }

            // 2. Technical units with digits: e.g. 4k -> 4K, 16gb -> 16GB, 1tb -> 1TB
            if (preg_match('/^([0-9]+)(k|gb|tb|mb|kb|ghz|mhz|khz)$/i', $token, $m)) {
                return $m[1] . mb_strtoupper($m[2], 'UTF-8');
            }

            // 3. Preserve already capitalized alphanumeric codes (e.g., FW-43EZ20L, INV-001, 27AAPFU0939F1ZV)
            if (preg_match('/[0-9]/', $token) && preg_match('/[A-Z]/', $token) && $token === mb_strtoupper($token, 'UTF-8')) {
                return $token;
            }

            // 4. Preserve intentional CamelCase / PascalCase brand names (e.g., OmniCore, TitanFlow, ThinkPad, YouTube)
            if (preg_match('/^[A-Z][a-z]+[A-Z]/', $token)) {
                return $token;
            }

            // 3. Handle apostrophes (e.g. supplier's, o'connor)
            if (str_contains($token, "'")) {
                $parts = explode("'", $token);
                if (count($parts) === 2 && mb_strtolower($parts[1], 'UTF-8') === 's') {
                    $baseLower = mb_strtolower($parts[0], 'UTF-8');
                    $base = static::$acronymMap[$baseLower] ?? (mb_strtoupper(mb_substr($baseLower, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($baseLower, 1, null, 'UTF-8'));
                    return $base . "'s";
                }
                return implode("'", array_map(function ($p) {
                    $pLower = mb_strtolower($p, 'UTF-8');
                    return static::$acronymMap[$pLower] ?? (mb_strtoupper(mb_substr($pLower, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($pLower, 1, null, 'UTF-8'));
                }, $parts));
            }

            // 4. Check compound tokens separated by slash, hyphen, or ampersand
            if (preg_match('/[\/\-\&]/', $token)) {
                $subParts = preg_split('/([\/\-\&])/', $token, -1, PREG_SPLIT_DELIM_CAPTURE);
                $result = '';
                foreach ($subParts as $part) {
                    if ($part === '/' || $part === '-' || $part === '&') {
                        $result .= $part;
                    } else {
                        $partLower = mb_strtolower($part, 'UTF-8');
                        if (isset(static::$acronymMap[$partLower])) {
                            $result .= static::$acronymMap[$partLower];
                        } elseif (preg_match('/[0-9]/', $part) && preg_match('/[A-Z]/', $part) && $part === mb_strtoupper($part, 'UTF-8')) {
                            $result .= $part;
                        } else {
                            $result .= mb_strtoupper(mb_substr($partLower, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($partLower, 1, null, 'UTF-8');
                        }
                    }
                }
                return $result;
            }

            // 5. Default title-case capitalization
            return mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($lower, 1, null, 'UTF-8');
        }, $normalized);
    }
}
