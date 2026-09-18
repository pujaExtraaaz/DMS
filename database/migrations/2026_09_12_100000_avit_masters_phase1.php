<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach ([
                'tan' => fn () => $table->string('tan', 20)->nullable()->after('cin'),
                'udyam_registration_no' => fn () => $table->string('udyam_registration_no', 30)->nullable()->after('tan'),
                'msme_category' => fn () => $table->enum('msme_category', ['none', 'micro', 'small', 'medium'])->default('none')->after('udyam_registration_no'),
                'msme_registration_no' => fn () => $table->string('msme_registration_no', 30)->nullable()->after('msme_category'),
                'website' => fn () => $table->string('website', 255)->nullable()->after('email'),
                'upi_id' => fn () => $table->string('upi_id', 100)->nullable()->after('bank_ifsc'),
                'additional_details' => fn () => $table->json('additional_details')->nullable()->after('upi_id'),
            ] as $column => $adder) {
                if (! Schema::hasColumn('companies', $column)) {
                    $adder();
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            foreach ([
                'sender_warranty_months' => fn () => $table->unsignedInteger('sender_warranty_months')->nullable()->after('warranty_months'),
                'sender_warranty_terms' => fn () => $table->text('sender_warranty_terms')->nullable()->after('sender_warranty_months'),
                'customer_warranty_months' => fn () => $table->unsignedInteger('customer_warranty_months')->nullable()->after('sender_warranty_terms'),
                'customer_warranty_terms' => fn () => $table->text('customer_warranty_terms')->nullable()->after('customer_warranty_months'),
                'color_variant' => fn () => $table->string('color_variant', 60)->nullable()->after('customer_warranty_terms'),
                'discount_type' => fn () => $table->enum('discount_type', ['percent', 'flat'])->default('percent')->after('color_variant'),
                'discount_value' => fn () => $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type'),
                'selling_discount_type' => fn () => $table->enum('selling_discount_type', ['percent', 'flat'])->default('percent')->after('discount_value'),
                'selling_discount_value' => fn () => $table->decimal('selling_discount_value', 12, 2)->default(0)->after('selling_discount_type'),
                'apply_discount_on_payable' => fn () => $table->boolean('apply_discount_on_payable')->default(false)->after('selling_discount_value'),
            ] as $column => $adder) {
                if (! Schema::hasColumn('products', $column)) {
                    $adder();
                }
            }
        });

        // Index new stock filter columns
        if (Schema::hasColumn('products', 'color_variant')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('color_variant');
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach (['tan', 'udyam_registration_no', 'msme_category', 'msme_registration_no', 'website', 'upi_id', 'additional_details'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            foreach ([
                'sender_warranty_months', 'sender_warranty_terms', 'customer_warranty_months',
                'customer_warranty_terms', 'color_variant', 'discount_type', 'discount_value',
                'selling_discount_type', 'selling_discount_value', 'apply_discount_on_payable',
            ] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
