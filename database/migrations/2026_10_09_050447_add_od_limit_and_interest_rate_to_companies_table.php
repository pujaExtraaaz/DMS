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
            if (! Schema::hasColumn('companies', 'od_limit')) {
                $table->decimal('od_limit', 15, 2)->default(0)->nullable()->after('upi_id');
            }
            if (! Schema::hasColumn('companies', 'interest_rate')) {
                $table->decimal('interest_rate', 6, 2)->default(0)->nullable()->after('od_limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'interest_rate')) {
                $table->dropColumn('interest_rate');
            }
            if (Schema::hasColumn('companies', 'od_limit')) {
                $table->dropColumn('od_limit');
            }
        });
    }
};
