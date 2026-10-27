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
        if (! Schema::hasTable('product_types')) {
            Schema::create('product_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('name', 100);
                $table->string('code', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique('name', 'product_types_name_unique');
            });

            // Seed default standard product types
            $defaults = ['FINISHED GOODS', 'HARDWARE', 'SPARE PART'];
            $now = now();
            foreach ($defaults as $idx => $typeName) {
                $code = match ($typeName) {
                    'FINISHED GOODS' => 'FG',
                    'HARDWARE' => 'HW',
                    'SPARE PART' => 'SP',
                    default => 'PT-' . ($idx + 1),
                };

                DB::table('product_types')->insertOrIgnore([
                    'name' => $typeName,
                    'code' => $code,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasColumn('products', 'product_type_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('product_type_id')
                    ->nullable()
                    ->after('model')
                    ->constrained('product_types')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'product_type_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_type_id');
            });
        }

        Schema::dropIfExists('product_types');
    }
};

