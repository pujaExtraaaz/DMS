<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('party_addresses', function (Blueprint $table) {
            $table->string('type', 50)->default('both')->change();

            if (! Schema::hasColumn('party_addresses', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('name');
            }
            if (! Schema::hasColumn('party_addresses', 'contact_phone')) {
                $table->string('contact_phone', 20)->nullable()->after('contact_person');
            }
            if (! Schema::hasColumn('party_addresses', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('contact_phone');
            }
            if (! Schema::hasColumn('party_addresses', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }
            if (! Schema::hasColumn('party_addresses', 'city')) {
                $table->string('city', 100)->nullable()->after('address_line_2');
            }
            if (! Schema::hasColumn('party_addresses', 'is_default_billing')) {
                $table->boolean('is_default_billing')->default(false)->after('is_default');
            }
            if (! Schema::hasColumn('party_addresses', 'is_default_delivery')) {
                $table->boolean('is_default_delivery')->default(false)->after('is_default_billing');
            }
            if (! Schema::hasColumn('party_addresses', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_default_delivery');
            }
        });

        // Migrate existing party_addresses records
        DB::table('party_addresses')->update([
            'is_default_billing' => DB::raw('is_default'),
            'is_default_delivery' => DB::raw('is_default'),
            'is_active' => true,
        ]);

        DB::table('party_addresses')
            ->whereNull('address_line_1')
            ->whereNotNull('address')
            ->update([
                'address_line_1' => DB::raw('address'),
            ]);

        // Auto-create default party_address for existing customers who have customer.address but no party_addresses
        $customersWithAddress = DB::table('customers')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('party_addresses')
                    ->whereColumn('party_addresses.customer_id', 'customers.id');
            })
            ->get();

        foreach ($customersWithAddress as $c) {
            DB::table('party_addresses')->insert([
                'customer_id' => $c->id,
                'type' => 'both',
                'label' => 'Main Office',
                'name' => $c->name,
                'contact_person' => $c->name,
                'contact_phone' => $c->phone,
                'address_line_1' => $c->address,
                'address' => $c->address,
                'state' => $c->state,
                'pincode' => $c->pincode,
                'gstin' => $c->gstin,
                'is_default' => true,
                'is_default_billing' => true,
                'is_default_delivery' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add billing and shipping address tracking to transaction tables
        $tables = ['invoices', 'orders', 'purchase_orders', 'purchase_invoices'];
        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    if (! Schema::hasColumn($tbl, 'billing_address_id')) {
                        $table->foreignId('billing_address_id')->nullable()->constrained('party_addresses')->nullOnDelete();
                    }
                    if (! Schema::hasColumn($tbl, 'shipping_address_id')) {
                        $table->foreignId('shipping_address_id')->nullable()->constrained('party_addresses')->nullOnDelete();
                    }
                    if (! Schema::hasColumn($tbl, 'billing_address')) {
                        $table->text('billing_address')->nullable();
                    }
                    if (! Schema::hasColumn($tbl, 'shipping_address')) {
                        $table->text('shipping_address')->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['purchase_invoices', 'purchase_orders', 'orders', 'invoices'];
        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    if (Schema::hasColumn($tbl, 'billing_address_id')) {
                        $table->dropForeign([$tbl === 'invoices' ? 'invoices_billing_address_id_foreign' : ($tbl.'_billing_address_id_foreign')]);
                        $table->dropColumn('billing_address_id');
                    }
                    if (Schema::hasColumn($tbl, 'shipping_address_id')) {
                        $table->dropForeign([$tbl === 'invoices' ? 'invoices_shipping_address_id_foreign' : ($tbl.'_shipping_address_id_foreign')]);
                        $table->dropColumn('shipping_address_id');
                    }
                    if (Schema::hasColumn($tbl, 'billing_address')) {
                        $table->dropColumn('billing_address');
                    }
                    if (Schema::hasColumn($tbl, 'shipping_address')) {
                        $table->dropColumn('shipping_address');
                    }
                });
            }
        }

        Schema::table('party_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'contact_person',
                'contact_phone',
                'address_line_1',
                'address_line_2',
                'city',
                'is_default_billing',
                'is_default_delivery',
                'is_active',
            ]);
            $table->enum('type', ['billing', 'shipping', 'site', 'warehouse'])->default('billing')->change();
        });
    }
};

