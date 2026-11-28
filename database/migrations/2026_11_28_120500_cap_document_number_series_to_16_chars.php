<?php

use App\Domains\Organization\Models\DocumentNumberSeries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * GST e-invoice DocDtls.No max length is 16. Shorten existing AVIT* series
 * prefixes so PREFIX-FY-SEQ never exceeds that limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_number_series')) {
            return;
        }

        DocumentNumberSeries::query()->orderBy('id')->each(function (DocumentNumberSeries $series) {
            $maxPrefix = $series->maxAllowedPrefixLength();
            $current = strtoupper(trim((string) $series->prefix));
            $short = DocumentNumberSeries::shortenPrefixForLimit(
                $current,
                (string) $series->document_type,
                $maxPrefix
            );

            $updates = [];
            if ($short !== $current) {
                $updates['prefix'] = $short;
                $series->prefix = $short;
            }

            // Ensure padding does not push over 16 even with shortened prefix.
            try {
                $preview = $series->preview();
            } catch (\Throwable) {
                $preview = str_repeat('X', DocumentNumberSeries::MAX_VOUCHER_NUMBER_LENGTH + 1);
            }

            if (strlen($preview) > DocumentNumberSeries::MAX_VOUCHER_NUMBER_LENGTH) {
                $pad = max(1, (int) $series->padding);
                while ($pad > 1) {
                    $pad--;
                    $series->padding = $pad;
                    try {
                        if (strlen($series->preview()) <= DocumentNumberSeries::MAX_VOUCHER_NUMBER_LENGTH) {
                            $updates['padding'] = $pad;
                            break;
                        }
                    } catch (\Throwable) {
                        // keep shrinking
                    }
                }
            }

            // Prefer compact yy_yy FY style (4 chars) over longer styles.
            if ($series->include_financial_year && ($series->financial_year_style ?? '') !== 'yy_yy') {
                $series->financial_year_style = 'yy_yy';
                $updates['financial_year_style'] = 'yy_yy';
                try {
                    if (strlen($series->preview()) > DocumentNumberSeries::MAX_VOUCHER_NUMBER_LENGTH) {
                        // leave as-is; prefix/pad already adjusted above
                    }
                } catch (\Throwable) {
                    // ignore
                }
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DocumentNumberSeries::query()->where('id', $series->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Irreversible data shortening — no-op.
    }
};
