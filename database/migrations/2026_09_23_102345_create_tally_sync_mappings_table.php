<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_sync_mappings', function (Blueprint $table) {
            $table->id();

            // DMS side
            $table->string('entity_type', 60);
            $table->unsignedBigInteger('entity_id');

            // Tally side
            $table->string('tally_type', 60);
            $table->string('tally_guid')->nullable();
            $table->string('tally_name')->nullable();

            // Last known sync direction/state
            $table->string('sync_status', 30)->default('synced');
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['tally_type', 'tally_guid']);

            $table->unique(
                ['entity_type', 'entity_id', 'tally_type'],
                'tally_mapping_dms_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_sync_mappings');
    }
};