<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_report_snapshots', function (Blueprint $table) {
            $table->string('report', 40)->primary();
            $table->string('title', 80);
            $table->string('period', 40);
            $table->string('status', 20)->default('ok');
            $table->unsignedInteger('rows')->default(0);
            $table->text('summary')->nullable();
            $table->string('message', 500)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_report_snapshots');
    }
};