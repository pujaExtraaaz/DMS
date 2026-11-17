<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false)->after('is_active')->index();
            }
        });

        // Seed new product visibility permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'products.view-hidden',
            'products.hide',
            'products.unhide',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_hidden')) {
                $table->dropIndex(['is_hidden']);
                $table->dropColumn('is_hidden');
            }
        });

        $permissions = [
            'products.view-hidden',
            'products.hide',
            'products.unhide',
        ];

        Permission::whereIn('name', $permissions)->where('guard_name', 'web')->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
