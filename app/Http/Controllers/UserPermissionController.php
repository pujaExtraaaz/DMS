<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    public function update(Request $request, User $user)
    {
        $rawPermissions = $request->input('permissions', []);
        $permissions = array_values(array_filter($rawPermissions, 'is_string'));

        $modules = [
            'dashboard',
            'products',
            'customers',
            'price-master',
            'orders',
            'stock',
            'purchases',
            'invoices',
            'payments',
            'communications',
            'logistics',
            'delivery',
            'settlement',
            'reports',
            'masters',
            'inventory',
            'sales',
        ];

        $actions = ['view', 'create', 'edit', 'manage'];

        $allowed = [];
        foreach ($modules as $mod) {
            foreach ($actions as $act) {
                $allowed[] = "{$mod}.{$act}";
            }
        }

        $validPermissions = array_values(array_intersect($permissions, $allowed));

        $extraPermissions = [];
        foreach ($validPermissions as $perm) {
            $parts = explode('.', $perm);
            if (count($parts) === 2) {
                [$mod, $act] = $parts;
                if (in_array($mod, ['products', 'customers', 'price-master'], true)) {
                    $extraPermissions[] = "masters.{$act}";
                }
                if (in_array($mod, ['stock', 'purchases'], true)) {
                    $extraPermissions[] = "inventory.{$act}";
                }
                if ($mod === 'invoices') {
                    $extraPermissions[] = "sales.{$act}";
                }
            }
        }

        $allToSync = array_values(array_unique(array_merge($validPermissions, $extraPermissions)));

        foreach ($allToSync as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        $user->syncPermissions($allToSync);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }
}