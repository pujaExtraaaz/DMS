<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('converted_by_name', 100)
                ->nullable()
                ->after('approved_at');

            $table->string('cancelled_by_name', 100)
                ->nullable()
                ->after('converted_by_name');

            $table->index('converted_by_name');
            $table->index('cancelled_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex([
                'converted_by_name',
            ]);

            $table->dropIndex([
                'cancelled_by_name',
            ]);

            $table->dropColumn([
                'converted_by_name',
                'cancelled_by_name',
            ]);
        });
    }
};