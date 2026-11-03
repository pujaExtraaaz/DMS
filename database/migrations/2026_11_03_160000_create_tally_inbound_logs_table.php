<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tally_inbound_logs')) {
            return;
        }

        Schema::create('tally_inbound_logs', function (Blueprint $table) {
            $table->id();
            $table->string('collection', 40)->index();
            $table->string('item', 255)->nullable();
            $table->string('action', 20)->default('synced'); // created|updated|synced|conflict|batch
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedBigInteger('alter_id')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_inbound_logs');
    }
};
