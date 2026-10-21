<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('price_masters', 'rate_includes_gst')) {
                $table->boolean('rate_includes_gst')->default(false)->after('rate');
            }
            if (! Schema::hasColumn('price_masters', 'entered_rate')) {
                $table->decimal('entered_rate', 12, 2)->nullable()->after('rate_includes_gst');
            }
        });

        DB::table('price_masters')->whereNull('entered_rate')->update([
            'entered_rate' => DB::raw('rate'),
        ]);
    }

    public function down(): void
    {
        Schema::table('price_masters', function (Blueprint $table) {
            if (Schema::hasColumn('price_masters', 'entered_rate')) {
                $table->dropColumn('entered_rate');
            }
            if (Schema::hasColumn('price_masters', 'rate_includes_gst')) {
                $table->dropColumn('rate_includes_gst');
            }
        });
    }
};
