<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uom_mappings')) {
            Schema::create('uom_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('imported_uom', 100)->unique();
                $table->foreignId('uom_id')->constrained('uoms')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_mappings');
    }
};

