<?php

namespace App\Policies;

use App\Domains\Order\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAny($user, [
            'orders.view',
            'orders.manage',
        ]);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->hasAny($user, [
            'orders.view',
            'orders.manage',
        ]);
    }

    public function create(User $user): bool
    {
        return $this->hasAny($user, [
            'orders.create',
            'orders.book',
            'orders.manage',
        ]);
    }

    public function update(User $user, Order $order): bool
    {
        if (! in_array($order->status, ['draft', 'pending'], true)) {
            return false;
        }

        return $this->hasAny($user, [
            'orders.edit',
            'orders.manage',
        ]);
    }

    public function approve(User $user, Order $order): bool
    {
        if (! in_array($order->status, ['draft', 'pending'], true)) {
            return false;
        }

        return $this->hasAny($user, [
            'orders.approve',
            'orders.manage',
        ]);
    }

    public function convert(User $user, Order $order): bool
    {
        if (! in_array($order->status, ['pending', 'approved'], true)) {
            return false;
        }

        return $this->hasAny($user, [
            'orders.convert',
            'orders.manage',
        ]);
    }

    public function cancel(User $user, Order $order): bool
    {
        if (! in_array($order->status, ['draft', 'pending', 'approved'], true)) {
            return false;
        }

        return $this->hasAny($user, [
            'orders.edit',
            'orders.manage',
        ]);
    }

    public function export(User $user): bool
    {
        return $this->hasAny($user, [
            'orders.view',
            'orders.manage',
        ]);
    }

    protected function hasAny(User $user, array $permissions): bool
    {
        return $user->hasRole('super-admin')
            || collect($permissions)->contains(
                fn (string $permission) => $user->can($permission)
            );
    }
}