<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DocumentNumberSeries extends Model
{
    /** GST e-invoice / e-way DocDtls.No hard limit (NIC / Masters India). */
    public const MAX_VOUCHER_NUMBER_LENGTH = 16;

    public const METHOD_AUTOMATIC = 'automatic';

    public const METHOD_AUTOMATIC_MANUAL_OVERRIDE = 'automatic_manual_override';

    public const METHOD_MANUAL = 'manual';

    public const METHOD_MULTI_USER_AUTO = 'multi_user_auto';

    public const PERIODICITY_DAILY = 'daily';

    public const PERIODICITY_WEEKLY = 'weekly';

    public const PERIODICITY_MONTHLY = 'monthly';

    public const PERIODICITY_YEARLY = 'yearly';

    public const PERIODICITY_NEVER = 'never';

    protected $fillable = [
        'company_id',
        'document_type',
        'name',
        'prefix',
        'suffix',
        'include_financial_year',
        'financial_year_style',
        'separator',
        'padding',
        'starting_number',
        'prefix_zero',
        'numbering_method',
        'restart_each_fy',
        'restart_rules',
        'prefix_rules',
        'suffix_rules',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'include_financial_year' => 'boolean',
            'restart_each_fy' => 'boolean',
            'is_active' => 'boolean',
            'padding' => 'integer',
            'starting_number' => 'integer',
            'prefix_zero' => 'boolean',
            'restart_rules' => 'array',
            'prefix_rules' => 'array',
            'suffix_rules' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isManual(): bool
    {
        return $this->numbering_method === self::METHOD_MANUAL;
    }

    public function isAutomatic(): bool
    {
        return $this->numbering_method === self::METHOD_AUTOMATIC;
    }

    public function isManualOverride(): bool
    {
        return $this->numbering_method === self::METHOD_AUTOMATIC_MANUAL_OVERRIDE;
    }

    public function isMultiUserAuto(): bool
    {
        return $this->numbering_method === self::METHOD_MULTI_USER_AUTO;
    }

    public function allowsManualEntry(): bool
    {
        return in_array($this->numbering_method, [self::METHOD_MANUAL, self::METHOD_AUTOMATIC_MANUAL_OVERRIDE], true);
    }

    public function requiresManualEntry(): bool
    {
        return $this->numbering_method === self::METHOD_MANUAL;
    }

    public function generatesAutomatically(): bool
    {
        return in_array($this->numbering_method, [
            self::METHOD_AUTOMATIC,
            self::METHOD_AUTOMATIC_MANUAL_OVERRIDE,
            self::METHOD_MULTI_USER_AUTO,
        ], true);
    }

    public function methodName(): string
    {
        return match ($this->numbering_method) {
            self::METHOD_AUTOMATIC => 'Automatic',
            self::METHOD_AUTOMATIC_MANUAL_OVERRIDE => 'Automatic (Manual Override)',
            self::METHOD_MANUAL => 'Manual',
            self::METHOD_MULTI_USER_AUTO => 'Multi-user Auto',
            default => ucfirst(str_replace('_', ' ', (string) $this->numbering_method)),
        };
    }

    public function financialYearCode(?FinancialYear $fy = null, ?Carbon $date = null): string
    {
        $fy ??= $this->resolveFinancialYear($date);
        if (! $fy) {
            $date ??= now();

            return $date->format('y').$date->copy()->addYear()->format('y');
        }

        $start = $fy->starts_on;
        $end = $fy->ends_on;

        return match ($this->financial_year_style) {
            'YYYY-YY' => $start->format('Y').'-'.$end->format('y'),
            'name' => (string) ($fy->name ?: ($start->format('y').$end->format('y'))),
            default => $start->format('y').$end->format('y'),
        };
    }

    public function resolveFinancialYear(?Carbon $date = null): ?FinancialYear
    {
        $date ??= now();
        $query = FinancialYear::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->orderByDesc('is_current');

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        return $query->first()
            ?? FinancialYear::query()->where('is_current', true)->when($this->company_id, fn ($q) => $q->where('company_id', $this->company_id))->first();
    }

    public function resolvePrefix(?Carbon $date = null): string
    {
        $date ??= now();
        $dateStr = $date->toDateString();

        if (! empty($this->prefix_rules) && is_array($this->prefix_rules)) {
            $sorted = collect($this->prefix_rules)
                ->filter(function ($rule) use ($dateStr) {
                    $from = ! empty($rule['applicable_from']) ? Carbon::parse($rule['applicable_from'])->toDateString() : null;
                    $to = ! empty($rule['applicable_to']) ? Carbon::parse($rule['applicable_to'])->toDateString() : null;
                    if (! $from) {
                        return false;
                    }
                    if ($from > $dateStr) {
                        return false;
                    }
                    if ($to && $to < $dateStr) {
                        return false;
                    }

                    return true;
                })
                ->sortByDesc('applicable_from')
                ->first();

            if ($sorted && isset($sorted['prefix']) && trim((string) $sorted['prefix']) !== '') {
                return trim((string) $sorted['prefix']);
            }
        }

        return trim((string) ($this->prefix ?? ''));
    }

    public function resolveSuffix(?Carbon $date = null): string
    {
        $date ??= now();
        $dateStr = $date->toDateString();

        if (! empty($this->suffix_rules) && is_array($this->suffix_rules)) {
            $sorted = collect($this->suffix_rules)
                ->filter(function ($rule) use ($dateStr) {
                    $from = ! empty($rule['applicable_from']) ? Carbon::parse($rule['applicable_from'])->toDateString() : null;
                    $to = ! empty($rule['applicable_to']) ? Carbon::parse($rule['applicable_to'])->toDateString() : null;
                    if (! $from) {
                        return false;
                    }
                    if ($from > $dateStr) {
                        return false;
                    }
                    if ($to && $to < $dateStr) {
                        return false;
                    }

                    return true;
                })
                ->sortByDesc('applicable_from')
                ->first();

            if ($sorted && isset($sorted['suffix']) && trim((string) $sorted['suffix']) !== '') {
                return trim((string) $sorted['suffix']);
            }
        }

        return trim((string) ($this->suffix ?? ''));
    }

    public function resolveRestartRule(?Carbon $date = null): ?array
    {
        $date ??= now();
        $dateStr = $date->toDateString();

        if (! empty($this->restart_rules) && is_array($this->restart_rules)) {
            return collect($this->restart_rules)
                ->filter(function ($rule) use ($dateStr) {
                    $from = ! empty($rule['applicable_from']) ? Carbon::parse($rule['applicable_from'])->toDateString() : null;
                    $to = ! empty($rule['applicable_to']) ? Carbon::parse($rule['applicable_to'])->toDateString() : null;
                    if (! $from) {
                        return false;
                    }
                    if ($from > $dateStr) {
                        return false;
                    }
                    if ($to && $to < $dateStr) {
                        return false;
                    }

                    return true;
                })
                ->sortByDesc('applicable_from')
                ->first();
        }

        return null;
    }

    public function formatNumber(int $number, ?FinancialYear $fy = null, ?Carbon $date = null): string
    {
        $date ??= now();
        $sep = $this->separator !== null && $this->separator !== '' ? $this->separator : '-';
        $parts = [];

        $activePrefix = $this->resolvePrefix($date);
        if ($activePrefix !== '') {
            $parts[] = strtoupper($activePrefix);
        }

        $fyCode = null;
        if ($this->include_financial_year) {
            $fyCode = $this->financialYearCode($fy, $date);
            $cleanPrefix = strtoupper(str_replace(['-', '/'], '', $activePrefix));
            $cleanFy = strtoupper(str_replace(['-', '/'], '', $fyCode));
            if ($activePrefix === '' || (! str_contains($cleanPrefix, $cleanFy))) {
                $parts[] = $fyCode;
            }
        }

        $width = max(1, min(8, (int) ($this->padding ?? 4)));
        $prefixZero = $this->prefix_zero ?? true;
        $num = max(0, $number);

        // Shrink zero-padding if needed so final voucher stays within GST 16-char limit.
        while ($width >= 1) {
            $numPart = $prefixZero ? sprintf('%0'.$width.'d', $num) : (string) $num;
            $body = implode($sep, array_merge($parts, [$numPart]));
            $activeSuffix = $this->resolveSuffix($date);
            $formatted = $activeSuffix === '' ? $body : $body.$activeSuffix;

            if (strlen($formatted) <= self::MAX_VOUCHER_NUMBER_LENGTH) {
                return $formatted;
            }

            if (! $prefixZero || $width <= strlen((string) $num)) {
                break;
            }
            $width--;
        }

        $numPart = $prefixZero ? sprintf('%0'.max(1, $width).'d', $num) : (string) $num;
        $body = implode($sep, array_merge($parts, [$numPart]));
        $activeSuffix = $this->resolveSuffix($date);
        $formatted = $activeSuffix === '' ? $body : $body.$activeSuffix;

        if (strlen($formatted) > self::MAX_VOUCHER_NUMBER_LENGTH) {
            throw new \RuntimeException(
                'Voucher number "'.$formatted.'" exceeds '.self::MAX_VOUCHER_NUMBER_LENGTH
                .' characters (GST e-invoice limit). Shorten the series prefix/suffix in Setup → Document Number Series.'
            );
        }

        return $formatted;
    }

    /**
     * Max prefix length that still fits MAX_VOUCHER_NUMBER_LENGTH for this series shape.
     */
    public function maxAllowedPrefixLength(?Carbon $date = null): int
    {
        $date ??= now();
        $sep = $this->separator !== null && $this->separator !== '' ? $this->separator : '-';
        $pad = max(1, min(8, (int) ($this->padding ?? 4)));
        $suffixLen = strlen($this->resolveSuffix($date));
        $used = $pad + $suffixLen;

        if ($this->include_financial_year) {
            $fyLen = strlen($this->financialYearCode(null, $date));
            $used += $fyLen + strlen($sep); // FY + separator before number
            $used += strlen($sep); // separator after prefix
        } else {
            $used += strlen($sep);
        }

        return max(1, self::MAX_VOUCHER_NUMBER_LENGTH - $used);
    }

    public static function shortenPrefixForLimit(string $prefix, string $documentType, int $maxLen): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix) ?? '');
        $doc = strtoupper(preg_replace('/[^A-Z0-9]/', '', $documentType) ?? 'DOC');

        if ($clean === '') {
            $clean = $doc;
        }

        if (strlen($clean) <= $maxLen) {
            return $clean;
        }

        if (str_starts_with($clean, 'AVIT') && strlen($clean) > 4) {
            $clean = substr($clean, 4) ?: $doc;
        }

        if (strlen($clean) <= $maxLen) {
            return $clean;
        }

        // Prefer A + doc type (e.g. AINV) when still too long
        $branded = 'A'.$doc;
        if (strlen($branded) <= $maxLen) {
            return $branded;
        }

        return substr($doc, 0, $maxLen);
    }

    public function preview(?Carbon $date = null): string
    {
        $date ??= now();
        $restartRule = $this->resolveRestartRule($date);
        $startingNo = max(1, (int) ($restartRule['starting_number'] ?? $this->starting_number ?? 1));

        return $this->formatNumber($startingNo, null, $date);
    }

    public static function periodicities(): array
    {
        return [
            self::PERIODICITY_DAILY => 'Daily',
            self::PERIODICITY_WEEKLY => 'Weekly',
            self::PERIODICITY_MONTHLY => 'Monthly',
            self::PERIODICITY_YEARLY => 'Yearly',
            self::PERIODICITY_NEVER => 'Never',
        ];
    }

    public static function documentTypes(): array
    {
        return [
            // Purchasing
            'PO' => 'Purchase Order',
            'PI' => 'Purchase Invoice',
            'GRN' => 'Goods Receipt',
            'RN' => 'Receipt Note',
            'DC-RET' => 'Delivery Challan Return',
            'FRT' => 'Freight Bill',
            'LC' => 'Landed Cost',
            'DN' => 'Debit Note',
            // Sales & orders
            'SO' => 'Sales Order',
            'ORD' => 'Order',
            'QTN' => 'Quotation',
            'INV' => 'Tax Invoice',
            'PRF' => 'Proforma Invoice',
            'CN' => 'Credit Note',
            'DC' => 'Delivery Challan',
            'SRV' => 'Service Ticket',
            // Money
            'PAY' => 'Payment',
            'SPAY' => 'Supplier Payment',
            'REC' => 'Receipt',
            'CON' => 'Contra',
            'JRN' => 'Journal',
            'INT' => 'Interest',
            // Inventory
            'ADJ' => 'Stock Journal',
            'TRF' => 'Stock Transfer',
            'SNT' => 'Stock Reclassification',
            'PHY' => 'Physical Stock',
            'RJI' => 'Rejections In',
            'RJO' => 'Rejections Out',
            // Job work / deals / schemes
            'JWO' => 'Job Work Out Order',
            'DEAL' => 'Deal',
            'SCH' => 'Scheme',
        ];
    }

    public static function numberingMethods(): array
    {
        return [
            self::METHOD_AUTOMATIC => [
                'name' => 'Automatic',
                'description' => 'Generate voucher numbers automatically using the configured prefix, separator and sequence.',
            ],
            self::METHOD_AUTOMATIC_MANUAL_OVERRIDE => [
                'name' => 'Automatic (Manual Override)',
                'description' => 'Generate numbers automatically by default, but allow authorized users to enter or modify the voucher number manually.',
            ],
            self::METHOD_MANUAL => [
                'name' => 'Manual',
                'description' => 'Require users to enter the voucher number themselves.',
            ],
            self::METHOD_MULTI_USER_AUTO => [
                'name' => 'Multi-user Auto',
                'description' => 'Generate unique voucher numbers automatically for multiple users creating vouchers simultaneously.',
            ],
        ];
    }
}
