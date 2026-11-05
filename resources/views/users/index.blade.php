@extends('layouts.dms')

@section('title', 'Users and Roles')

@section('content')

@php
    $permissionKeys = $permissionKeys ?? [];
    $roleLabel = [\App\Http\Controllers\UserController::class, 'roleLabel'];
    $directory = $users->map(function ($user) use ($roleLabel) {
        $role = $user->roles->first()?->name;

        return [
            'id' => $user->id,
            'hay' => strtolower(trim($user->name.' '.$user->email.' '.($user->phone ?? '').' '.$roleLabel($role))),
        ];
    })->values();
    $avatarTones = [
        'bg-indigo-100 text-indigo-700',
        'bg-emerald-100 text-emerald-700',
        'bg-sky-100 text-sky-700',
        'bg-amber-100 text-amber-800',
        'bg-rose-100 text-rose-700',
        'bg-violet-100 text-violet-700',
    ];
    $roleTones = [
        'super-admin' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'client-admin' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'owner' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'sales-manager' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'salesperson' => 'bg-cyan-50 text-cyan-800 ring-cyan-200',
        'finance' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'accounts' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'warehouse' => 'bg-orange-50 text-orange-800 ring-orange-200',
        'driver' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'delivery-person' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-200',
        'purchase' => 'bg-lime-50 text-lime-800 ring-lime-200',
        'management' => 'bg-blue-50 text-blue-700 ring-blue-200',
    ];
@endphp

<div
    x-data="{
        permissionUser: null,
        selectedPermissions: [],
        allPermissions: @js($permissionKeys),
        permissionMode: 'custom',
        saving: false,
        message: '',
        formOpen: {{ $errors->any() && (old('form') === 'create' || str_starts_with((string) old('form'), 'edit-')) ? 'true' : 'false' }},
        formMode: '{{ old('form') && str_starts_with((string) old('form'), 'edit-') ? 'edit' : 'create' }}',
        editId: {{ old('form') && str_starts_with((string) old('form'), 'edit-') ? (int) str_replace('edit-', '', (string) old('form')) : 'null' }},
        editUrl: '',
        newRoleName: '',
        creatingRole: false,
        roleMessage: '',
        q: '',
        permQuery: '',
        createPermQuery: '',
        people: @js($directory),
        photoPreview: '',

        rowVisible(id) {
            const person = this.people.find(item => item.id === id)
            const needle = this.q.trim().toLowerCase()
            return needle === '' || (person?.hay || '').includes(needle)
        },

        init() {
            if (this.formMode === 'edit' && this.editId) {
                this.editUrl = '{{ url('/users') }}/' + this.editId
            }
        },

        openCreate() {
            this.formMode = 'create'
            this.editId = null
            this.editUrl = ''
            this.photoPreview = ''
            this.createPermQuery = ''
            this.roleMessage = ''
            this.formOpen = true
            this.$nextTick(() => this.fillForm({ name: '', phone: '', email: '', role: '', permissions: [] }))
        },

        openEditor(user) {
            this.formMode = 'edit'
            this.editId = user.id
            this.editUrl = '{{ url('/users') }}/' + user.id
            this.photoPreview = user.avatar || ''
            this.createPermQuery = ''
            this.roleMessage = ''
            this.formOpen = true
            this.$nextTick(() => this.fillForm(user))
        },

        closeForm() {
            this.formOpen = false
        },

        fillForm(user) {
            const form = document.getElementById('user-editor-form')
            if (!form) return
            form.querySelector('[name=name]').value = user.name || ''
            form.querySelector('[name=phone]').value = user.phone || ''
            form.querySelector('[name=email]').value = user.email || ''
            const password = form.querySelector('[name=password]')
            if (password) password.value = ''
            const select = form.querySelector('#create_role')
            if (select) {
                select.value = user.role || ''
                select.dispatchEvent(new Event('change', { bubbles: true }))
            }
            const granted = user.permissions || []
            form.querySelectorAll('[data-create-permission]').forEach(box => {
                box.checked = granted.includes(box.value)
            })
        },

        onPhoto(event) {
            const file = event.target.files && event.target.files[0]
            if (!file) {
                this.photoPreview = ''
                return
            }
            const reader = new FileReader()
            reader.onload = (e) => { this.photoPreview = e.target.result }
            reader.readAsDataURL(file)
        },

        openPermissions(user) {
            this.permissionUser = user
            this.selectedPermissions = [...(user.permissions ?? [])]
            this.permissionMode = this.isAllSelected() ? 'all' : 'custom'
            this.message = ''
            this.permQuery = ''
        },

        closePermissions() {
            this.permissionUser = null
            this.message = ''
        },

        moduleMatches(label, query) {
            const needle = (query || '').trim().toLowerCase()
            return needle === '' || String(label).toLowerCase().includes(needle)
        },

        toggle(module, action) {
            const permission = module + '.' + action
            if (this.selectedPermissions.includes(permission)) {
                this.selectedPermissions = this.selectedPermissions.filter(p => p !== permission)
            } else {
                this.selectedPermissions.push(permission)
            }
            this.permissionMode = this.isAllSelected() ? 'all' : 'custom'
        },

        has(module, action) {
            return this.selectedPermissions.includes(module + '.' + action)
        },

        isAllSelected() {
            return this.allPermissions.length > 0 && this.allPermissions.every(p => this.selectedPermissions.includes(p))
        },

        selectAll() {
            this.permissionMode = 'all'
            const extras = this.selectedPermissions.filter(p => !this.allPermissions.includes(p))
            this.selectedPermissions = [...this.allPermissions, ...extras]
            document.querySelectorAll('[data-create-permission]').forEach(box => { box.checked = true })
        },

        clearAll() {
            this.permissionMode = 'custom'
            this.selectedPermissions = this.selectedPermissions.filter(p => !this.allPermissions.includes(p))
            document.querySelectorAll('[data-create-permission]').forEach(box => { box.checked = false })
        },

        save() {
            this.saving = true
            this.message = ''

            fetch('{{ url('/users') }}/' + this.permissionUser.id + '/permissions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    permissions: this.selectedPermissions
                })
            })
            .then(r => r.json())
            .then(data => {
                this.saving = false
                if (data.success) {
                    this.message = data.message || 'Permissions updated successfully.'
                    if (data.permissions && this.permissionUser) {
                        this.permissionUser.permissions = data.permissions
                        this.selectedPermissions = [...data.permissions]
                    }
                } else {
                    this.message = data.message || 'Unable to update permissions.'
                }
            })
            .catch(() => {
                this.saving = false
                this.message = 'Unable to update permissions.'
            })
        },

        async saveRole() {
            const name = (this.newRoleName || '').trim()
            if (!name) {
                this.roleMessage = 'Enter a role name.'
                return
            }
            this.creatingRole = true
            this.roleMessage = ''
            try {
                const response = await fetch('{{ route('users.roles.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ role_name: name })
                })
                const data = await response.json()
                if (!response.ok) {
                    this.roleMessage = data.message || 'Unable to create the role.'
                    return
                }
                document.querySelectorAll('select[data-role-select]').forEach(select => {
                    if (![...select.options].some(option => option.value === data.name)) {
                        const option = document.createElement('option')
                        option.value = data.name
                        option.textContent = data.label
                        select.appendChild(option)
                    }
                })
                const createSelect = document.querySelector('#create_role')
                if (createSelect) {
                    createSelect.value = data.name
                    createSelect.dispatchEvent(new Event('change', { bubbles: true }))
                }
                this.newRoleName = ''
                this.roleMessage = data.label + ' is ready to use.'
            } catch (e) {
                this.roleMessage = 'Unable to create the role.'
            } finally {
                this.creatingRole = false
            }
        }
    }"
    @keydown.escape.window="closePermissions(); closeForm()"
>

    <x-ui.page-header title="Users and Roles">
        @if($isSuperAdmin)
            <x-slot name="actions">
                <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <span class="text-base leading-none">+</span> Add User
                </button>
            </x-slot>
        @endif
    </x-ui.page-header>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3">
            <label class="relative block w-full sm:max-w-sm">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.48 9.78l3.62 3.62a.75.75 0 1 0 1.06-1.06l-3.62-3.62A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/></svg>
                </span>
                <input x-model="q" type="search" placeholder="Search name, email, phone, or role" class="w-full rounded-lg border-slate-200 py-2 pl-9 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left">
                        <th class="px-4 py-3 font-semibold text-slate-900">Name / Email</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Phone</th>
                        <th class="px-4 py-3 font-semibold text-slate-900">Role</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $canEditRow = $isSuperAdmin || ! $user->hasRole('super-admin');
                            $currentRole = $user->roles->first()?->name;
                            $nameParts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
                            $initials = strtoupper(mb_substr($nameParts[0] ?? '?', 0, 1).(isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''));
                            $tone = $avatarTones[abs(crc32((string) $user->email)) % count($avatarTones)];
                            $badge = $roleTones[$currentRole] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr x-show="rowVisible({{ $user->id }})" class="border-b border-slate-100 last:border-0">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($user->avatarUrl())
                                        <img src="{{ $user->avatarUrl() }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                    @else
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $tone }}">{{ $initials }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $user->name }}</p>
                                        <p class="truncate text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $user->phone ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badge }}">{{ $roleLabel($currentRole) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($canEditRow)
                                    <button
                                        type="button"
                                        @click="openEditor({
                                            id: {{ $user->id }},
                                            name: @js($user->name),
                                            phone: @js($user->phone ?? ''),
                                            email: @js($user->email),
                                            role: @js($currentRole ?? ''),
                                            avatar: @js($user->avatarUrl() ?? ''),
                                            permissions: @js($user->getAllPermissions()->pluck('name')->values()->all())
                                        })"
                                        class="text-sm font-semibold text-indigo-700 hover:text-indigo-900"
                                    >Edit</button>
                                @endif
                                @if($isSuperAdmin && auth()->id() !== $user->id)
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Remove {{ $user->name }} from the list? The account stays in the database.')" class="ml-4 inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-rose-600 hover:text-rose-800">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr x-show="people.filter(person => rowVisible(person.id)).length === 0" x-cloak>
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No users match that search.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

        <div x-show="formOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-3 backdrop-blur-[2px]">
            <form id="user-editor-form" method="POST" :action="formMode === 'edit' ? editUrl : '{{ route('users.store') }}'" enctype="multipart/form-data" @click.outside="closeForm()" class="flex w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5" style="max-height: min(640px, 85vh);">
                @csrf
                <input type="hidden" name="_method" :value="formMode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="form" :value="formMode === 'edit' ? 'edit-' + editId : 'create'">
                <div class="flex items-center gap-3 px-4 py-3 text-white" style="background: linear-gradient(90deg, #4f46e5, #7c3aed);">
                    <label class="relative flex h-12 w-12 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-full ring-2 ring-white/80" style="background: rgba(255,255,255,.18);">
                        <img x-show="photoPreview" :src="photoPreview" alt="" class="h-full w-full object-cover">
                        <span x-show="!photoPreview" class="text-lg font-semibold leading-none">+</span>
                        <input type="file" name="avatar" accept="image/*" class="sr-only" @change="onPhoto($event)">
                    </label>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-semibold" x-text="formMode === 'edit' ? 'Edit User' : 'Add User'"></h2>
                        <p class="text-xs text-indigo-100" x-text="formMode === 'edit' ? 'Photo and password can stay as they are. The other fields are required.' : 'Tap the circle for a photo. Everything else is required.'"></p>
                    </div>
                    <button type="button" @click="closeForm()" class="text-2xl leading-none text-white/80 hover:text-white">&times;</button>
                </div>
                <div class="space-y-3 overflow-y-auto px-4 py-3">
                    <div class="grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Name</span>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Full name" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-indigo-500">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Phone</span>
                            <input type="text" name="phone" value="{{ old('phone') }}" required maxlength="20" placeholder="10-digit number" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-indigo-500">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Email</span>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="name@company.com" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-indigo-500">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Change Password</span>
                            <input type="password" name="password" :required="formMode === 'create'" minlength="8" autocomplete="new-password" :placeholder="formMode === 'edit' ? 'Leave blank to keep' : 'At least 8 characters'" class="h-9 w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-indigo-500">
                        </label>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-2.5">
                        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Role</span>
                        <div class="flex items-center gap-1.5">
                            <select id="create_role" name="role" data-role-select required class="h-9 min-w-0 flex-1 rounded-lg border-slate-200 bg-white text-sm">
                                <option value="">Select role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $roleLabel($role->name) }}</option>
                                @endforeach
                            </select>
                            @if($isSuperAdmin)
                                <input type="text" x-model="newRoleName" @keydown.enter.prevent="saveRole()" placeholder="New role" class="h-9 w-28 rounded-lg border-slate-200 bg-white px-2.5 text-sm">
                                <button type="button" @click="saveRole()" :disabled="creatingRole" class="h-9 shrink-0 rounded-lg bg-slate-900 px-2.5 text-xs font-semibold text-white disabled:opacity-50">Add</button>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-indigo-600" x-show="roleMessage" x-text="roleMessage"></p>
                    </div>
                    <div>
                        <div class="mb-1.5 flex items-center gap-2">
                            <p class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Permissions</p>
                            <input x-model="createPermQuery" type="search" placeholder="Find a module" class="h-8 min-w-0 flex-1 rounded-lg border-slate-200 bg-slate-50 px-2.5 text-xs">
                            <label class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                <input type="radio" name="create_permission_mode" value="all" @change="selectAll()" class="text-indigo-600 focus:ring-indigo-500">
                                Select all
                            </label>
                        </div>
                        <div class="space-y-0.5 overflow-y-auto rounded-xl border border-slate-100 p-1.5" style="max-height: 9rem;">
                            @foreach(\App\Support\DmsPermissionCatalog::uiModules() as $module => $key)
                                <div x-show="moduleMatches(@js($module), createPermQuery)" class="flex items-center justify-between gap-2 rounded-lg px-2 py-1 hover:bg-slate-50">
                                    <span class="truncate text-xs font-medium text-slate-700">{{ $module }}</span>
                                    <span class="flex shrink-0 gap-2">
                                        @foreach(\App\Support\DmsPermissionCatalog::uiActions() as $action)
                                            <label class="flex items-center gap-1 text-[11px] text-slate-500">
                                                <input type="checkbox" name="permissions[]" value="{{ $key }}.{{ $action }}" data-create-permission class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600" @checked(in_array($key.'.'.$action, old('permissions', []), true))>
                                                {{ ucfirst($action) }}
                                            </label>
                                        @endforeach
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-4 py-2.5">
                    <button type="button" @click="closeForm()" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-white">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-3.5 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700" x-text="formMode === 'edit' ? 'Save User' : 'Create User'"></button>
                </div>
            </form>
        </div>

    <div x-show="permissionUser" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div x-show="permissionUser" x-transition @click.outside="closePermissions()" class="relative flex w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl" style="max-height: 88vh;">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900" x-text="permissionUser ? permissionUser.name : 'Permissions'"></h2>
                    <p class="mt-0.5 text-sm text-slate-500" x-text="permissionUser?.role"></p>
                </div>
                <button type="button" @click="closePermissions()" class="text-2xl leading-none text-slate-400 hover:text-slate-700">&times;</button>
            </div>
            <div class="flex-1 space-y-3 overflow-y-auto p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input x-model="permQuery" type="search" placeholder="Find a module" class="w-full rounded-xl border-slate-200 text-sm">
                    <label class="inline-flex shrink-0 cursor-pointer items-center gap-2 rounded-full bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">
                        <input type="radio" name="permission_mode" value="all" :checked="permissionMode === 'all'" @change="selectAll()" class="text-indigo-600 focus:ring-indigo-500">
                        Select all
                    </label>
                </div>
                @foreach(\App\Support\DmsPermissionCatalog::uiModules() as $module => $key)
                    <div x-show="moduleMatches(@js($module), permQuery)" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 hover:bg-slate-50">
                        <span class="font-medium text-slate-800">{{ $module }}</span>
                        <div class="flex shrink-0 items-center gap-5">
                            @foreach(\App\Support\DmsPermissionCatalog::uiActions() as $action)
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" :checked="has('{{ $key }}', '{{ $action }}')" @change="toggle('{{ $key }}', '{{ $action }}')" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>{{ ucfirst($action) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex shrink-0 items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50 px-5 py-3">
                <span x-show="message" x-text="message" class="mr-auto text-sm font-medium text-emerald-600"></span>
                <button type="button" @click="closePermissions()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">Cancel</button>
                <button type="button" @click="save()" :disabled="saving" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                    <span x-show="!saving">Save Changes</span>
                    <span x-show="saving">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
