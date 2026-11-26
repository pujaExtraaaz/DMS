<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasTable('stock_movements')) {
                DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
                    'purchase','sale','adjustment','return','delivery_short',
                    'transfer_in','transfer_out','opening','inward','reclassification',
                    'purchase_invoice','consumption','manufacture','scrap',
                    'receipt_note','receipt_note_return','purchase_return'
                ) NOT NULL");
            }

            if (Schema::hasTable('supplier_payables')) {
                DB::statement("ALTER TABLE supplier_payables MODIFY COLUMN type ENUM(
                    'invoice','payment','adjustment','debit_note','credit_note','payment_reversal'
                ) NOT NULL DEFAULT 'invoice'");
            }
        }

        if (! Schema::hasTable('document_number_series')) {
            return;
        }

        $exists = DB::table('document_number_series')
            ->where('document_type', 'SPAY')
            ->whereNull('company_id')
            ->exists();

        if (! $exists) {
            $now = now();
            DB::table('document_number_series')->insert([
                'company_id' => null,
                'document_type' => 'SPAY',
                'name' => 'Supplier Payment',
                'prefix' => 'AVITSPAY',
                'suffix' => null,
                'include_financial_year' => true,
                'financial_year_style' => 'yy_yy',
                'separator' => '-',
                'padding' => 4,
                'starting_number' => 1,
                'prefix_zero' => true,
                'numbering_method' => 'automatic',
                'restart_each_fy' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasTable('stock_movements')) {
                DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
                    'purchase','sale','adjustment','return','delivery_short',
                    'transfer_in','transfer_out','opening','inward','reclassification',
                    'purchase_invoice','consumption','manufacture','scrap',
                    'receipt_note','receipt_note_return'
                ) NOT NULL");
            }

            if (Schema::hasTable('supplier_payables')) {
                DB::statement("ALTER TABLE supplier_payables MODIFY COLUMN type ENUM(
                    'invoice','payment','adjustment','debit_note','credit_note'
                ) NOT NULL DEFAULT 'invoice'");
            }
        }
    }
};
