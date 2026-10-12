<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'industry')) {
                $table->string('industry', 100)->nullable()->index()->after('tag');
            }
        });

        if (! Schema::hasTable('lead_segments')) {
            Schema::create('lead_segments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->json('criteria');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_segments');

        if (Schema::hasColumn('leads', 'industry')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('industry');
            });
        }
    }
};

