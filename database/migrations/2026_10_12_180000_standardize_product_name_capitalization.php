<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $products = DB::table('products')->select('id', 'name')->get();

        foreach ($products as $product) {
            if ($product->name === null) {
                continue;
            }

            $trimmed = trim($product->name);
            if ($trimmed === '') {
                continue;
            }

            $normalized = Str::ucfirst($trimmed);
            if ($normalized !== $product->name) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['name' => $normalized]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Name capitalization changes do not need reversal.
    }
};

