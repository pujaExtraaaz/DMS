<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditLogger;
use Tally\Authorization\PermissionCatalog;
use Tally\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('tally::roles.index', [
            'roles' => Role::query()->withCount('users')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('tally::roles.form', [
            'role' => new Role(['permissions' => []]),
            'groups' => PermissionCatalog::grouped(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $role = Role::query()->create($this->validated($request));
        $audit->record('role_created', 'roles', $role, 'Role '.$role->name.' created.');

        return redirect()->route('books.tally.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        return view('tally::roles.form', [
            'role' => $role,
            'groups' => PermissionCatalog::grouped(),
        ]);
    }

    public function update(Request $request, Role $role, AuditLogger $audit): RedirectResponse
    {
        $role->update($this->validated($request, $role));
        $audit->record('role_updated', 'roles', $role, 'Role '.$role->name.' updated.');

        return redirect()->route('books.tally.roles.index')->with('status', 'Role updated.');
    }

    public function updateActivation(Request $request, Role $role, AuditLogger $audit): RedirectResponse
    {
        if ($role->is_system && ! $request->boolean('is_active')) {
            return back()->with('error', 'A system role cannot be deactivated.');
        }

        $active = $request->boolean('is_active');
        $role->update(['is_active' => $active]);
        $audit->record($active ? 'role_activated' : 'role_deactivated', 'roles', $role, 'Role '.$role->name.' '.($active ? 'activated' : 'deactivated').'.');

        return back()->with('status', $active ? 'Role activated.' : 'Role deactivated. Users with this role lose its permissions until it is activated.');
    }

    public function destroy(Role $role, AuditLogger $audit): RedirectResponse
    {
        if ($role->is_system) {
            return back()->with('error', 'A system role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'This role is assigned to users and cannot be deleted.');
        }

        $audit->record('role_deleted', 'roles', $role, 'Role '.$role->name.' deleted.');
        $role->delete();

        return redirect()->route('books.tally.roles.index')->with('status', 'Role deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->ignore($role)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::all())],
        ]);

        return [
            'name' => $data['name'],
            'permissions' => array_values($data['permissions'] ?? []),
            'is_system' => (bool) ($role?->is_system ?? false),
        ];
    }
}
