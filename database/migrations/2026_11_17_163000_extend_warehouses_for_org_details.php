<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        Schema::table('warehouses', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouses', 'warehouse_type')) {
                $table->string('warehouse_type', 100)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'address_line_2')) {
                $table->string('address_line_2', 255)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'state')) {
                $table->string('state', 100)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'country')) {
                $table->string('country', 100)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'pincode')) {
                $table->string('pincode', 12)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'contact_person')) {
                $table->string('contact_person', 255)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'contact_phone')) {
                $table->string('contact_phone', 30)->nullable();
            }
            if (! Schema::hasColumn('warehouses', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        if (Schema::hasColumn('warehouses', 'country')) {
            DB::table('warehouses')->where(function ($q) {
                $q->whereNull('country')->orWhere('country', '');
            })->update(['country' => 'India']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        Schema::table('warehouses', function (Blueprint $table) {
            foreach ([
                'warehouse_type',
                'contact_person',
                'contact_phone',
                'address_line_2',
                'city',
                'state',
                'country',
                'pincode',
                'notes',
            ] as $col) {
                if (Schema::hasColumn('warehouses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
