<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('qr_token', 36)->nullable()->unique()->after('invoice_no');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('qr_token', 36)->nullable()->unique()->after('invoice_no');
        });

        DB::table('invoices')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->eachById(function ($invoice) {
                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'qr_token' => (string) Str::uuid(),
                    ]);
            });

        DB::table('purchase_invoices')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->eachById(function ($invoice) {
                DB::table('purchase_invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'qr_token' => (string) Str::uuid(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};