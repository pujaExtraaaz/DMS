<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Safely resolve existing duplicate brand names before applying the unique index.
        $allBrands = DB::table('brands')->select('id', 'name', 'code')->orderBy('id')->get();
        $grouped = $allBrands->groupBy(fn ($b) => strtolower(trim((string) $b->name)));

        foreach ($grouped as $normalizedName => $group) {
            if ($group->count() > 1) {
                // Keep the primary (first) brand name untouched.
                $duplicates = $group->slice(1);

                foreach ($duplicates as $dupe) {
                    $suffix = filled($dupe->code) ? $dupe->code : 'Dup-'.$dupe->id;
                    DB::table('brands')
                        ->where('id', $dupe->id)
                        ->update(['name' => trim((string) $dupe->name) . ' (' . $suffix . ')']);
                }
            }
        }

        // 2. Add the unique constraint to the name column.
        Schema::table('brands', function (Blueprint $table) {
            $table->unique('name', 'brands_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique('brands_name_unique');
        });
    }
};