<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_sync_cursors', function (Blueprint $table) {
            $table->string('collection', 40)->primary();
            $table->unsignedBigInteger('alter_id')->default(0);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('tally_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('collection', 40);
            $table->string('remote_id', 120)->nullable();
            $table->string('name', 200)->nullable();
            $table->text('reason');
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_sync_conflicts');
        Schema::dropIfExists('tally_sync_cursors');
    }
};