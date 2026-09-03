<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberService
{
    public function next(
        string $prefix,
        ?Carbon $date = null,
        int $padding = 4
    ): string {
        $date ??= now();

        return DB::transaction(function () use ($prefix, $date, $padding) {
            DB::table('document_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'sequence_date' => $date->toDateString(),
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('document_sequences')
                ->where('prefix', $prefix)
                ->whereDate('sequence_date', $date->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException(
                    "Unable to initialize document sequence for {$prefix}."
                );
            }

            $nextNumber = ((int) $sequence->last_number) + 1;

            DB::table('document_sequences')
                ->where('id', $sequence->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return sprintf(
                '%s-%s-%0*d',
                $prefix,
                $date->format('Ymd'),
                $padding,
                $nextNumber
            );
        });
    }
}