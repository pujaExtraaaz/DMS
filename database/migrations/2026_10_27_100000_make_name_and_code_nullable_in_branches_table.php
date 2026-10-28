<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                if (Schema::hasColumn('branches', 'name')) {
                    $table->string('name', 255)->nullable()->change();
                }
                if (Schema::hasColumn('branches', 'code')) {
                    $table->string('code', 30)->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                if (Schema::hasColumn('branches', 'name')) {
                    $table->string('name', 255)->nullable(false)->change();
                }
                if (Schema::hasColumn('branches', 'code')) {
                    $table->string('code', 30)->nullable(false)->change();
                }
            });
        }
    }
};

