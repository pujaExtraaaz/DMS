<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'gst_registration_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('gst_registration_type', 50)->nullable()->after('gstin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'gst_registration_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('gst_registration_type');
            });
        }
    }
};

