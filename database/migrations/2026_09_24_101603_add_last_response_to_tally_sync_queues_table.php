<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tally_sync_queues', function (Blueprint $table) {
            $table->longText('last_response')->nullable()->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('tally_sync_queues', function (Blueprint $table) {
            $table->dropColumn('last_response');
        });
    }
};