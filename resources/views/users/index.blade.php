@extends('layouts.dms')

@section('title', 'Users')

@section('content')

<div
    x-data="{
        permissionUser: null,
        selectedPermissions: [],
        saving: false,
        message: '',

        openPermissions(user) {
            this.permissionUser = user
            this.selectedPermissions = user.permissions ?? []
            this.message = ''
        },

        closePermissions() {
            this.permissionUser = null
            this.message = ''
        },

        toggle(module, action) {
            const permission = module + '.' + action

            if (this.selectedPermissions.includes(permission)) {
                this.selectedPermissions = this.selectedPermissions.filter(p => p !== permission)
            } else {
                this.selectedPermissions.push(permission)
            }
        },

        has(module, action) {
            return this.selectedPermissions.includes(module + '.' + action)
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
        }
    }"
    @keydown.escape.window="closePermissions()"
>

    <x-ui.page-header title="Users" />

    <x-ui.card>
        <x-ui.table>

            <x-slot name="head">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Role
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                        Action
                    </th>
                </tr>
            </x-slot>

            @foreach($users as $user)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium">
                        {{ $user->name }}
                    </td>

                    <td class="px-6 py-4 text-sm">
                        {{ $user->email }}
                    </td>

                    <td class="px-6 py-4 text-sm">
                        {{ str_replace('-', ' ', $user->roles->first()?->name ?? 'No Role') }}
                    </td>

                    <td class="px-6 py-4 text-right">
                        <button
                            type="button"
                            @click="openPermissions({
                                id: {{ $user->id }},
                                name: @js($user->name),
                                role: @js($user->roles->first()?->name ?? 'No Role'),
                                permissions: @js($user->getAllPermissions()->pluck('name')->toArray())
                            })"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            Edit Perms
                        </button>
                    </td>
                </tr>
            @endforeach

        </x-ui.table>
    </x-ui.card>


    {{-- PERMISSION MODAL --}}
    <div
        x-show="permissionUser"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >

        {{-- Modal --}}
        <div
            x-show="permissionUser"
            x-transition
            @click.outside="closePermissions()"
            class="relative flex w-full max-w-2xl flex-col rounded-xl bg-white shadow-2xl"
            style="max-height: 85vh;"
        >

            {{-- Header --}}
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4">

                <div>
                    <h2 class="font-semibold text-gray-900">
                        Edit Permission
                    </h2>

                    <p
                        class="mt-1 text-sm text-gray-500"
                        x-text="permissionUser?.role"
                    ></p>
                </div>

                <button
                    type="button"
                    @click="closePermissions()"
                    class="text-xl text-gray-400 hover:text-gray-700"
                >
                    &times;
                </button>

            </div>


            {{-- Body --}}
            <div class="flex-1 space-y-3 overflow-y-auto p-6">

                <p class="text-sm text-gray-500">
                    Select what this user can access.
                </p>

                @foreach([
                    'Dashboard',
                    'Products',
                    'Customers',
                    'Price Master',
                    'Orders',
                    'Stock',
                    'Purchases',
                    'Invoices',
                    'Payments',
                    'Communications',
                    'Logistics',
                    'Delivery',
                    'Settlement',
                    'Reports'
                ] as $module)

                    @php($key = \Illuminate\Support\Str::slug($module))

                    <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50">

                        <span class="font-medium text-gray-800">
                            {{ $module }}
                        </span>

                        {{-- Permission Options --}}
                        <div class="flex shrink-0 items-center gap-6">

                            @foreach(['view', 'create', 'edit'] as $action)

                                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">

                                    <input
                                        type="checkbox"
                                        :checked="has('{{ $key }}', '{{ $action }}')"
                                        @change="toggle('{{ $key }}', '{{ $action }}')"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >

                                    <span>{{ ucfirst($action) }}</span>

                                </label>

                            @endforeach

                        </div>

                    </div>

                @endforeach

            </div>


            {{-- Footer --}}
            <div class="flex shrink-0 justify-end space-x-3 rounded-b-xl border-t border-gray-100 bg-gray-50/50 p-4">

                <span
                    x-show="message"
                    x-text="message"
                    class="mr-auto self-center text-sm font-medium text-green-600"
                ></span>

                <button
                    type="button"
                    @click="closePermissions()"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="save()"
                    :disabled="saving"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span x-show="!saving">
                        Save Changes
                    </span>

                    <span x-show="saving">
                        Saving...
                    </span>
                </button>

            </div>

        </div>

    </div>

</div>

@endsection