<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_invoices', 'material_receipt_status')) {
                    $table->string('material_receipt_status', 32)->default('in_transit')->after('status');
                }
                if (! Schema::hasColumn('purchase_invoices', 'material_dispatch_date')) {
                    $table->date('material_dispatch_date')->nullable()->after('purchase_receive_date');
                }
                if (! Schema::hasColumn('purchase_invoices', 'expected_delivery_date')) {
                    $table->date('expected_delivery_date')->nullable()->after('material_dispatch_date');
                }
                if (! Schema::hasColumn('purchase_invoices', 'actual_material_receipt_date')) {
                    $table->date('actual_material_receipt_date')->nullable()->after('expected_delivery_date');
                }
            });

            // Existing invoices with a receive date are treated as already received.
            if (Schema::hasColumn('purchase_invoices', 'purchase_receive_date')) {
                DB::table('purchase_invoices')
                    ->whereNotNull('purchase_receive_date')
                    ->update([
                        'material_receipt_status' => 'received',
                        'actual_material_receipt_date' => DB::raw('purchase_receive_date'),
                    ]);
            }
        }

        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_invoice_items', 'received_qty')) {
                    $table->decimal('received_qty', 15, 4)->default(0)->after('quantity');
                }
            });

            // Backfill received qty for historically received invoices.
            if (Schema::hasColumn('purchase_invoices', 'material_receipt_status')) {
                $receivedIds = DB::table('purchase_invoices')
                    ->where('material_receipt_status', 'received')
                    ->pluck('id');

                if ($receivedIds->isNotEmpty()) {
                    DB::table('purchase_invoice_items')
                        ->whereIn('purchase_invoice_id', $receivedIds)
                        ->update(['received_qty' => DB::raw('quantity')]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_invoice_items') && Schema::hasColumn('purchase_invoice_items', 'received_qty')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                $table->dropColumn('received_qty');
            });
        }

        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                foreach (['material_receipt_status', 'material_dispatch_date', 'expected_delivery_date', 'actual_material_receipt_date'] as $col) {
                    if (Schema::hasColumn('purchase_invoices', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
