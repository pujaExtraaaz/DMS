<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('e_way_bills', function (Blueprint $table) {
            $table->string('qr_token', 36)->nullable()->unique()->after('eway_bill_no');
        });

        DB::table('e_way_bills')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->eachById(function ($eWayBill) {
                DB::table('e_way_bills')
                    ->where('id', $eWayBill->id)
                    ->update([
                        'qr_token' => (string) Str::uuid(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('e_way_bills', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};