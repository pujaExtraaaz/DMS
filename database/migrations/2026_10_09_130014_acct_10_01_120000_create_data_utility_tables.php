<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_data_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('filename');
            $table->string('checksum', 64)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('status', 20);
            $table->json('table_counts')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });

        Schema::create('acct_data_restores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_backup_id')->nullable()->constrained('acct_data_backups')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('safety_backup_id')->nullable()->constrained('acct_data_backups')->nullOnDelete();
            $table->string('status', 20);
            $table->string('confirmation', 40);
            $table->json('table_counts')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_data_restores');
        Schema::dropIfExists('acct_data_backups');
    }
};
