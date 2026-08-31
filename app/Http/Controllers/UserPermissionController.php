<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    public function update(Request $request, User $user)
    {
        $permissions = $request->input('permissions', []);

        $allowed = [
            'dashboard.view',
            'dashboard.create',
            'dashboard.edit',
            'masters.view',
            'masters.create',
            'masters.edit',
            'orders.view',
            'orders.create',
            'orders.edit',
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'sales.view',
            'sales.create',
            'sales.edit',
            'payments.view',
            'payments.create',
            'payments.edit',
            'communications.view',
            'communications.create',
            'communications.edit',
            'logistics.view',
            'logistics.create',
            'logistics.edit',
            'delivery.view',
            'delivery.create',
            'delivery.edit',
            'settlement.view',
            'settlement.create',
            'settlement.edit',
            'reports.view',
            'reports.create',
            'reports.edit',
        ];

        $user->syncPermissions(
            array_values(array_intersect($permissions, $allowed))
        );

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
        ]);
    }
}