<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_claims', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('description');
            $table->decimal('settlement_amount', 12, 2)->nullable()->after('approval_notes');
            $table->string('settlement_reference', 100)->nullable()->after('settlement_amount');
            $table->text('settlement_notes')->nullable()->after('settlement_reference');
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete()->after('settlement_notes');
            $table->timestamp('settled_at')->nullable()->after('settled_by');
        });
    }

    public function down(): void
    {
        Schema::table('expense_claims', function (Blueprint $table) {
            $table->dropForeign(['settled_by']);
            $table->dropColumn([
                'receipt_path',
                'settlement_amount',
                'settlement_reference',
                'settlement_notes',
                'settled_by',
                'settled_at',
            ]);
        });
    }
};

