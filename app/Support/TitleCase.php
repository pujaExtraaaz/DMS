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
        // Tokenize words, hyphens, and slashes
        return preg_replace_callback('/[A-Za-z0-9]+(?:[\/\-\&][A-Za-z0-9]+)*/', function ($matches) {
            $token = $matches[0];
            $lower = strtolower($token);

            // 1. Check known acronyms or terms
            if (isset(static::$acronymMap[$lower])) {
                return static::$acronymMap[$lower];
            }

            // 2. Check compound tokens separated by slash, hyphen, or ampersand
            if (preg_match('/[\/\-\&]/', $token)) {
                $subParts = preg_split('/([\/\-\&])/', $token, -1, PREG_SPLIT_DELIM_CAPTURE);
                $result = '';
                foreach ($subParts as $part) {
                    if ($part === '/' || $part === '-' || $part === '&') {
                        $result .= $part;
                    } else {
                        $partLower = strtolower($part);
                        if (isset(static::$acronymMap[$partLower])) {
                            $result .= static::$acronymMap[$partLower];
                        } else {
                            $result .= ucfirst($partLower);
                        }
                    }
                }
                return $result;
            }

            // 3. Preserve mixed alphanumeric codes (e.g., FW-43EZ20L, INV-001)
            if (preg_match('/[0-9]/', $token) && preg_match('/[A-Z]/', $token)) {
                return $token;
            }

            // 4. Default title-case capitalization
            return ucfirst($lower);
        }, $text);
    }
}
