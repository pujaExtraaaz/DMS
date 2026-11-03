<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_delivery_addresses')) {
            Schema::create('company_delivery_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('address_type', 120);
                $table->string('mailing_name')->nullable();
                $table->text('address')->nullable();
                $table->string('state', 100)->nullable();
                $table->string('country', 100)->nullable()->default('India');
                $table->string('pincode', 20)->nullable();
                $table->string('gstin', 20)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['company_id', 'is_active']);
            });
        }

        if (Schema::hasTable('purchase_orders') && ! Schema::hasColumn('purchase_orders', 'company_delivery_address_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->foreignId('company_delivery_address_id')
                    ->nullable()
                    ->after('shipping_address_id')
                    ->constrained('company_delivery_addresses')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'company_delivery_address_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_delivery_address_id');
            });
        }

        Schema::dropIfExists('company_delivery_addresses');
    }
};
