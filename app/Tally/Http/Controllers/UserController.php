<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditLogger;
use Tally\Authorization\PermissionCatalog;
use Tally\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('tally::users.index', [
            'users' => User::query()->with('role')->orderByDesc('owner')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('tally::users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::query()->orderBy('name')->get(),
            'companies' => \Tally\Models\Company::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'owner' => null,
            'is_active' => $request->boolean('is_active', true),
            'role_id' => $data['role_id'] ?: null,
        ]);
        $user->companies()->sync($data['company_ids'] ?? []);
        $user->branches()->sync($data['branch_ids'] ?? []);
        $audit->record('user_created', 'users', $user, 'User '.$user->email.' created.');

        return redirect()->route('books.tally.users.show', $user)->with('status', 'User created.');
    }

    public function show(User $account): View
    {
        $account->load(['role', 'companies', 'branches']);

        return view('tally::users.show', ['account' => $account]);
    }

    public function edit(User $account): View
    {
        $account->load(['companies', 'branches']);

        return view('tally::users.form', [
            'user' => $account,
            'roles' => Role::query()->orderBy('name')->get(),
            'companies' => \Tally\Models\Company::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $account, AuditLogger $audit): RedirectResponse
    {
        $this->guardOwner($account, $request);
        $data = $this->validated($request, $account);
        $account->update([
            'name' => $data['name'],
            'email' => tally_super_admin($account) ? $account->email : $data['email'],
            'is_active' => tally_super_admin($account) ? true : $request->boolean('is_active'),
            'role_id' => tally_super_admin($account) ? null : ($data['role_id'] ?: null),
        ]);

        if (! tally_super_admin($account)) {
            $account->companies()->sync($data['company_ids'] ?? []);
            $account->branches()->sync($data['branch_ids'] ?? []);
        }

        $audit->record('user_updated', 'users', $account, 'User '.$account->email.' updated.');

        return redirect()->route('books.tally.users.show', $account)->with('status', 'User updated.');
    }

    public function updateActivation(Request $request, User $account, AuditLogger $audit): RedirectResponse
    {
        if (tally_super_admin($account)) {
            throw ValidationException::withMessages([
                'is_active' => 'The Super Admin cannot be deactivated.',
            ]);
        }

        $request->validate(['is_active' => ['required', 'boolean']]);
        $account->update(['is_active' => $request->boolean('is_active')]);
        $audit->record('user_status', 'users', $account, $account->email.' is now '.($account->is_active ? 'active' : 'inactive').'.');

        return back()->with('status', $account->is_active ? 'User activated.' : 'User deactivated.');
    }

    public function resetPassword(Request $request, User $account, AuditLogger $audit): RedirectResponse
    {
        if (tally_super_admin($account)) {
            return back()->with('error', 'The Super Admin password cannot be changed from user administration.');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $account->update(['password' => $data['password']]);
        $audit->record('user_password_reset', 'users', $account, 'Password reset for '.$account->email.'.');

        return back()->with('status', 'Password updated.');
    }

    public function destroy(User $account, AuditLogger $audit): RedirectResponse
    {
        if (tally_super_admin($account) || $account->is(auth()->user())) {
            return back()->with('error', 'The Super Admin account cannot be deleted.');
        }

        if (\Tally\Models\Voucher::query()->where('created_by', $account->id)->exists()
            || \Tally\Models\Invoice::query()->where('created_by', $account->id)->exists()) {
            return back()->with('error', 'This user has created accounting documents and cannot be deleted.');
        }

        $email = $account->email;
        $account->delete();
        $audit->record('user_deleted', 'users', null, 'User '.$email.' deleted.');

        return redirect()->route('books.tally.users.index')->with('status', 'User deleted.');
    }

    private function guardOwner(User $account, Request $request): void
    {
        if (tally_super_admin($account) && ! $request->boolean('is_active', true)) {
            throw ValidationException::withMessages([
                'is_active' => 'The Super Admin cannot be deactivated.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $account = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account)],
            'password' => [$account ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
