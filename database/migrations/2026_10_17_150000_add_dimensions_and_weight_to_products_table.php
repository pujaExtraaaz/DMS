<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'height_cm')) {
                $table->decimal('height_cm', 10, 2)->nullable()->after('warranty');
            }
            if (! Schema::hasColumn('products', 'length_cm')) {
                $table->decimal('length_cm', 10, 2)->nullable()->after('height_cm');
            }
            if (! Schema::hasColumn('products', 'width_cm')) {
                $table->decimal('width_cm', 10, 2)->nullable()->after('length_cm');
            }
            if (! Schema::hasColumn('products', 'height_in')) {
                $table->decimal('height_in', 10, 2)->nullable()->after('width_cm');
            }
            if (! Schema::hasColumn('products', 'length_in')) {
                $table->decimal('length_in', 10, 2)->nullable()->after('height_in');
            }
            if (! Schema::hasColumn('products', 'width_in')) {
                $table->decimal('width_in', 10, 2)->nullable()->after('length_in');
            }
            if (! Schema::hasColumn('products', 'net_weight')) {
                $table->decimal('net_weight', 10, 3)->nullable()->after('width_in');
            }
            if (! Schema::hasColumn('products', 'weight_unit')) {
                $table->string('weight_unit', 20)->nullable()->default('KG')->after('net_weight');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'height_cm',
                'length_cm',
                'width_cm',
                'height_in',
                'length_in',
                'width_in',
                'net_weight',
                'weight_unit',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

