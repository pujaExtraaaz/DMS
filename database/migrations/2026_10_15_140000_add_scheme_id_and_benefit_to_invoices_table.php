<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('scheme_id')->nullable()->after('customer_id')->constrained('schemes')->nullOnDelete();
            $table->decimal('scheme_benefit_amount', 14, 2)->default(0)->after('grand_total');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['scheme_id']);
            $table->dropColumn(['scheme_id', 'scheme_benefit_amount']);
        });
    }
};

