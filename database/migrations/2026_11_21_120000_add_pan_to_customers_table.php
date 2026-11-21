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
        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'pan')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('pan', 20)->nullable()->after('gstin');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'pan')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('pan');
            });
        }
    }
};
