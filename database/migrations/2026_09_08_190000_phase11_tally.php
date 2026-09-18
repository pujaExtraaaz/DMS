<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_sync_queues', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 60); // invoice|payment|credit_note|purchase_invoice
            $table->unsignedBigInteger('document_id');
            $table->longText('payload')->nullable();
            $table->string('status', 30)->default('pending'); // pending|sent|failed|skipped
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_sync_queues');
    }
};
