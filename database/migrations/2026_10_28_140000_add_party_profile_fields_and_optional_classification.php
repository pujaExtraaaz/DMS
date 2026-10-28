<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $this->dropCustomerTypeConstraint();

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_type_id')->nullable()->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('customer_type_id')
                ->references('id')
                ->on('customer_types')
                ->nullOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'udyam_number')) {
                $table->string('udyam_number', 50)->nullable()->after('email');
            }
            if (! Schema::hasColumn('customers', 'gem_registered')) {
                $table->boolean('gem_registered')->default(false)->after('udyam_number');
            }
            if (! Schema::hasColumn('customers', 'gem_authorization_code')) {
                $table->string('gem_authorization_code', 100)->nullable()->after('gem_registered');
            }
            if (! Schema::hasColumn('customers', 'gem_valid_until')) {
                $table->date('gem_valid_until')->nullable()->after('gem_authorization_code');
            }
            if (! Schema::hasColumn('customers', 'website')) {
                $table->string('website', 255)->nullable()->after('gem_valid_until');
            }
            if (! Schema::hasColumn('customers', 'location_link')) {
                $table->string('location_link', 500)->nullable()->after('website');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            foreach (['location_link', 'website', 'gem_valid_until', 'gem_authorization_code', 'gem_registered', 'udyam_number'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $this->dropCustomerTypeConstraint();

        $fallbackId = DB::table('customer_types')->value('id');
        if ($fallbackId) {
            DB::table('customers')->whereNull('customer_type_id')->update([
                'customer_type_id' => $fallbackId,
            ]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_type_id')->nullable(false)->change();
            $table->foreign('customer_type_id')->references('id')->on('customer_types');
        });
    }

    private function dropCustomerTypeConstraint(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $foreignKey = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'customers_customer_type_id_foreign'");

            Schema::table('customers', function (Blueprint $table) use ($foreignKey) {
                if ($foreignKey) {
                    $table->dropForeign('customers_customer_type_id_foreign');
                } else {
                    $table->dropIndex('customers_customer_type_id_foreign');
                }
            });

            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['customer_type_id']);
        });
    }
};
