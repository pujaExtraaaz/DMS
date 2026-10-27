<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            if (! Schema::hasColumn('product_types', 'track_serial')) {
                $table->boolean('track_serial')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            if (Schema::hasColumn('product_types', 'track_serial')) {
                $table->dropColumn('track_serial');
            }
        });
    }
};
