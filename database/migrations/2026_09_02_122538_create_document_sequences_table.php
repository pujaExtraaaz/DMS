<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_sequences')) {
            return;
        }

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();

            $table->string('prefix', 20);
            $table->date('sequence_date');
            $table->unsignedInteger('last_number')->default(0);

            $table->timestamps();

            $table->unique(
                ['prefix', 'sequence_date'],
                'document_sequences_prefix_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};