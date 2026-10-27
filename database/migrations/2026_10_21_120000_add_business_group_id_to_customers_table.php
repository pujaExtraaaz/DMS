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
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'business_group_id')) {
                $table->foreignId('business_group_id')
                    ->nullable()
                    ->after('customer_type_id')
                    ->constrained('business_groups')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'business_group_id')) {
                $table->dropForeign(['business_group_id']);
                $table->dropColumn('business_group_id');
            }
        });
    }
};

