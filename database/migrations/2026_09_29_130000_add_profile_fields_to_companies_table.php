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
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'logo_path')) {
                $table->string('logo_path', 255)->nullable()->after('code');
            }
            if (! Schema::hasColumn('companies', 'alternate_phone')) {
                $table->string('alternate_phone', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('companies', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (! Schema::hasColumn('companies', 'country')) {
                $table->string('country', 100)->default('India')->nullable()->after('pincode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['logo_path', 'alternate_phone', 'city', 'country'] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};