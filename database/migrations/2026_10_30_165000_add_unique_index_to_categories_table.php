<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Resolve existing duplicate category names under the same brand safely without deleting any records
        $duplicates = DB::table('categories')
            ->select('brand_id', DB::raw('LOWER(TRIM(name)) as norm_name'), DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('brand_id')
            ->groupBy('brand_id', DB::raw('LOWER(TRIM(name))'))
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $rows = DB::table('categories')
                ->where('brand_id', $dup->brand_id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [$dup->norm_name])
                ->orderBy('id', 'asc')
                ->get();

            $isFirst = true;
            foreach ($rows as $row) {
                if ($isFirst) {
                    $isFirst = false;
                    continue;
                }

                // Disambiguate duplicate name to preserve all rows, relationships, and history
                DB::table('categories')
                    ->where('id', $row->id)
                    ->update([
                        'name' => $row->name . ' (Duplicate #' . $row->id . ')',
                    ]);
            }
        }

        // 2. Add the unique constraint on (brand_id, name)
        Schema::table('categories', function (Blueprint $table) {
            $table->unique(['brand_id', 'name'], 'categories_brand_id_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_brand_id_name_unique');
        });
    }
};

