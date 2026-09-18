<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_reservations')) {
            return;
        }

        Schema::table('stock_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_reservations', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('stock_reservations', 'order_item_id')) {
                $table->foreignId('order_item_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('stock_reservations', 'due_date')) {
                $table->date('due_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('stock_reservations', 'reserved_at')) {
                $table->timestamp('reserved_at')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('stock_reservations', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('reserved_at');
            }
            if (! Schema::hasColumn('stock_reservations', 'notes')) {
                $table->text('notes')->nullable()->after('released_at');
            }
        });

        // Align status set with OrderService / model expectations.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_reservations MODIFY COLUMN status ENUM('active','released','fulfilled','partial','consumed','cancelled') NOT NULL DEFAULT 'active'");
        }

        // Backfill order_id from morph reference when possible.
        if (Schema::hasColumn('stock_reservations', 'reference_type') && Schema::hasColumn('stock_reservations', 'reference_id')) {
            DB::table('stock_reservations')
                ->whereNull('order_id')
                ->where(function ($q) {
                    $q->where('reference_type', 'like', '%Order')
                        ->orWhere('reference_type', 'orders')
                        ->orWhere('reference_type', 'App\\Domains\\Order\\Models\\Order');
                })
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('stock_reservations')->where('id', $row->id)->update([
                            'order_id' => $row->reference_id,
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_reservations')) {
            return;
        }

        Schema::table('stock_reservations', function (Blueprint $table) {
            foreach (['order_id', 'order_item_id', 'due_date', 'reserved_at', 'released_at', 'notes'] as $column) {
                if (Schema::hasColumn('stock_reservations', $column)) {
                    if (in_array($column, ['order_id', 'order_item_id'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
