<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'pan')) {
                $table->string('pan', 20)->nullable()->after('gstin');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'pan')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('pan');
        });
    }
};
