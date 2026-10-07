<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_reclassifications')) {
            Schema::create('stock_reclassifications', function (Blueprint $table) {
                $table->id();
                $table->string('reclassification_no', 40)->unique();
                $table->date('reclassification_date');
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('from_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('to_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('uom_id')->constrained('uoms')->cascadeOnDelete();
                $table->decimal('quantity', 14, 4);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
                'purchase','sale','adjustment','return','delivery_short',
                'transfer_in','transfer_out','opening','inward','reclassification'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reclassifications');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
                'purchase','sale','adjustment','return','delivery_short',
                'transfer_in','transfer_out','opening','inward'
            ) NOT NULL");
        }
    }
};

