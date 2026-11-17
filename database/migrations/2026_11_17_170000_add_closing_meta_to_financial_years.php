<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            if (! Schema::hasColumn('financial_years', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('is_closed');
            }
            if (! Schema::hasColumn('financial_years', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            if (Schema::hasColumn('financial_years', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }
            if (Schema::hasColumn('financial_years', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
        });
    }
};
