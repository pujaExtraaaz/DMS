<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('price_masters', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('brand_id')->constrained('companies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('price_masters', function (Blueprint $table) {
            if (Schema::hasColumn('price_masters', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};
