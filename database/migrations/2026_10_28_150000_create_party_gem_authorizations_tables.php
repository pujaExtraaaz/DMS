<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('party_gem_authorizations')) {
            Schema::create('party_gem_authorizations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
                $table->boolean('gem_registered')->default(false);
                $table->boolean('gem_authorized')->default(false);
                $table->string('authorization_number', 100)->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'brand_id']);
            });
        }

        if (! Schema::hasTable('party_gem_documents')) {
            Schema::create('party_gem_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('party_gem_authorization_id')->constrained('party_gem_authorizations')->cascadeOnDelete();
                $table->string('disk', 20)->default('public');
                $table->string('path');
                $table->string('original_name');
                $table->string('mime', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('party_gem_documents');
        Schema::dropIfExists('party_gem_authorizations');
    }
};
