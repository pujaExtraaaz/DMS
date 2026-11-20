<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cheques')) {
            Schema::table('cheques', function (Blueprint $table) {
                if (! Schema::hasColumn('cheques', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('cheques', 'bank_account_no')) {
                    $table->string('bank_account_no', 64)->nullable()->after('company_id');
                }
                if (! Schema::hasColumn('cheques', 'cheque_book_id')) {
                    $table->foreignId('cheque_book_id')->nullable()->after('bank_account_no')->constrained('cheque_books')->nullOnDelete();
                }
                if (! Schema::hasColumn('cheques', 'cheque_type')) {
                    $table->string('cheque_type', 20)->default('received')->after('direction');
                }
                if (! Schema::hasColumn('cheques', 'reference_voucher')) {
                    $table->string('reference_voucher', 100)->nullable()->after('payment_id');
                }
                if (! Schema::hasColumn('cheques', 'voucher_type')) {
                    $table->string('voucher_type', 50)->nullable()->after('reference_voucher');
                }
                if (! Schema::hasColumn('cheques', 'voucher_id')) {
                    $table->unsignedBigInteger('voucher_id')->nullable()->after('voucher_type');
                }
                if (! Schema::hasColumn('cheques', 'stale_date')) {
                    $table->date('stale_date')->nullable()->after('clearance_date');
                }
                if (! Schema::hasColumn('cheques', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('bounced_at');
                }
                if (! Schema::hasColumn('cheques', 'cancelled_reason')) {
                    $table->string('cancelled_reason', 255)->nullable()->after('cancelled_at');
                }
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE cheques MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'received'");
                DB::statement("ALTER TABLE cheques MODIFY COLUMN direction VARCHAR(40) NOT NULL DEFAULT 'received_from_client'");
                DB::statement("ALTER TABLE cheques MODIFY COLUMN customer_id BIGINT UNSIGNED NULL");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cheques')) {
            Schema::table('cheques', function (Blueprint $table) {
                $columns = [
                    'company_id',
                    'bank_account_no',
                    'cheque_book_id',
                    'cheque_type',
                    'reference_voucher',
                    'voucher_type',
                    'voucher_id',
                    'stale_date',
                    'cancelled_at',
                    'cancelled_reason',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('cheques', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
