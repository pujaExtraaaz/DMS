<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DmsPermissionCatalog;
use App\Support\TitleCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['roles', 'permissions'])
            ->get()
            ->sortBy(function ($user) {
                $roleRank = [
                    'super-admin' => 1,
                    'owner' => 2,
                    'sales-manager' => 3,
                    'salesperson' => 4,
                    'warehouse' => 5,
                    'finance' => 6,
                    'driver' => 7,
                    'delivery-person' => 8,
                ][$user->roles->first()?->name] ?? 99;

                return sprintf('%02d_%s', $roleRank, strtolower(trim((string) $user->name)));
            });

        $permissionKeys = [];
        foreach (DmsPermissionCatalog::uiModules() as $module) {
            foreach (DmsPermissionCatalog::uiActions() as $action) {
                $permissionKeys[] = $module.'.'.$action;
            }
        }

        return view('users.index', [
            'users' => $users,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'permissionKeys' => $permissionKeys,
            'isSuperAdmin' => (bool) auth()->user()?->hasRole('super-admin'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'password' => ['required', 'string', 'min:8'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = User::create([
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->user()->branch_id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
            $user->save();
        }

        $user->assignRole($validated['role']);
        UserPermissionController::apply($user, $validated['permissions']);

        return redirect()
            ->route('users.index')
            ->with('status', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanEdit($request, $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'password' => ['nullable', 'string', 'min:8'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
        ]);
        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }
        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }
        $user->save();
        $user->syncRoles([$validated['role']]);
        UserPermissionController::apply($user, $validated['permissions']);

        return redirect()
            ->route('users.index')
            ->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        if ($user->is($request->user())) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'User deleted.');
    }

    public function storeRole(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100'],
        ]);

        $slug = Str::slug($validated['role_name']);
        if ($slug === '') {
            return response()->json(['message' => 'Enter a role name.'], 422);
        }

        $role = Role::firstOrCreate([
            'name' => $slug,
            'guard_name' => 'web',
        ]);

        return response()->json([
            'name' => $role->name,
            'label' => self::roleLabel($role->name),
        ]);
    }

    public static function roleLabel(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'No Role';
        }

        return TitleCase::format(str_replace(['-', '_'], ' ', $name));
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);
    }

    private function ensureCanEdit(Request $request, User $user): void
    {
        abort_unless($request->user()?->hasAnyRole(['super-admin', 'client-admin']), 403);

        if (! $request->user()->hasRole('super-admin') && $user->hasRole('super-admin')) {
            abort(403, 'Only a super admin can change another super admin.');
        }
    }
}
