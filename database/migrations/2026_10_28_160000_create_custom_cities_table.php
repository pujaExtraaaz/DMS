<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_cities', function (Blueprint $table) {
            $table->id();
            $table->string('state');
            $table->string('name');
            $table->timestamps();

            $table->unique(['state', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_cities');
    }
};
