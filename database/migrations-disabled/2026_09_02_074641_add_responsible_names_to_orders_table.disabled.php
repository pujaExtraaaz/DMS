<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('created_by_name', 100)
                ->nullable()
                ->after('salesperson_id');

            $table->string('updated_by_name', 100)
                ->nullable()
                ->after('created_by_name');

            $table->string('approved_by_name', 100)
                ->nullable()
                ->after('approved_by');

            $table->index('created_by_name');
            $table->index('updated_by_name');
            $table->index('approved_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_by_name']);
            $table->dropIndex(['updated_by_name']);
            $table->dropIndex(['approved_by_name']);

            $table->dropColumn([
                'created_by_name',
                'updated_by_name',
                'approved_by_name',
            ]);
        });
    }
};