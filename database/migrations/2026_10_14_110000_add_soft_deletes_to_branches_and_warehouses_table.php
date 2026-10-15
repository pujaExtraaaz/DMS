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
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('warehouses', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouses', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

