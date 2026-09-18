<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('serial_no', 30)->nullable()->unique()->after('sku');
        });

        // Give existing products a unique serial number based on their ID.
        DB::table('products')
            ->orderBy('id')
            ->eachById(function ($product) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'serial_no' => 'PRD-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT),
                    ]);
            });

        Schema::table('products', function (Blueprint $table) {
            $table->string('serial_no', 30)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['serial_no']);
            $table->dropColumn('serial_no');
        });
    }
};