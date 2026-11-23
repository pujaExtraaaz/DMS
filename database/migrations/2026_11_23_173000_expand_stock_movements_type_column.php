<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
                'purchase','sale','adjustment','return','delivery_short',
                'transfer_in','transfer_out','opening','inward','reclassification',
                'purchase_invoice','consumption','manufacture','scrap',
                'receipt_note','receipt_note_return'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
                'purchase','sale','adjustment','return','delivery_short',
                'transfer_in','transfer_out','opening','inward','reclassification',
                'purchase_invoice','consumption','manufacture','scrap'
            ) NOT NULL");
        }
    }
};
