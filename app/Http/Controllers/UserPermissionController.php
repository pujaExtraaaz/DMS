<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DmsPermissionCatalog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    public function update(Request $request, User $user)
    {
        $rawPermissions = $request->input('permissions', []);
        $permissions = array_values(array_filter($rawPermissions, 'is_string'));

        $modules = array_values(DmsPermissionCatalog::uiModules());
        $actions = array_merge(DmsPermissionCatalog::uiActions(), ['manage', 'delete']);

        $allowed = [];
        foreach ($modules as $mod) {
            foreach ($actions as $act) {
                $allowed[] = "{$mod}.{$act}";
            }
        }

        foreach (DmsPermissionCatalog::extras() as $extra) {
            $allowed[] = $extra;
        }

        $allowed = array_values(array_unique($allowed));
        $validPermissions = array_values(array_intersect($permissions, $allowed));

        $extraPermissions = [];
        foreach ($validPermissions as $perm) {
            $parts = explode('.', $perm);
            if (count($parts) !== 2) {
                continue;
            }

            [$mod, $act] = $parts;
            foreach (DmsPermissionCatalog::linkedPermissions($mod, $act) as $linked) {
                $extraPermissions[] = $linked;
            }
        }

        $allToSync = array_values(array_unique(array_merge($validPermissions, $extraPermissions)));

        foreach ($allToSync as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // Keep any direct extras/manage/delete grants that are not part of the
        // Edit Perms checkboxes so saving the modal does not strip them.
        $preserve = $user->getDirectPermissions()
            ->pluck('name')
            ->filter(function (string $name) use ($modules) {
                if (in_array($name, DmsPermissionCatalog::extras(), true)) {
                    return true;
                }

                $parts = explode('.', $name);
                if (count($parts) !== 2) {
                    return true;
                }

                [$mod, $act] = $parts;
                if (! in_array($mod, $modules, true)) {
                    return true;
                }

                return in_array($act, ['manage', 'delete'], true);
            })
            ->all();

        $allToSync = array_values(array_unique(array_merge($allToSync, $preserve)));

        $user->syncPermissions($allToSync);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }
}
