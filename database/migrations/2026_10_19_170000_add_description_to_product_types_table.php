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
        if (Schema::hasTable('product_types') && ! Schema::hasColumn('product_types', 'description')) {
            Schema::table('product_types', function (Blueprint $table) {
                $table->text('description')->nullable()->after('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('product_types') && Schema::hasColumn('product_types', 'description')) {
            Schema::table('product_types', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};

