<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_types')) {
            Schema::create('warehouse_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $defaults = [
            'Main Warehouse',
            'Transit',
            'Cold Storage',
            'Bonded',
            'Returns',
        ];

        foreach ($defaults as $name) {
            $exists = DB::table('warehouse_types')
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();

            if (! $exists) {
                DB::table('warehouse_types')->insert([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_types');
    }
};
