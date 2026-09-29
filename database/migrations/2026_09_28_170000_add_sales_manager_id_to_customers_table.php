<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'sales_manager_id')) {
                $table->foreignId('sales_manager_id')
                    ->nullable()
                    ->after('salesperson_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'sales_manager_id')) {
                $table->dropConstrainedForeignId('sales_manager_id');
            }
        });
    }
};