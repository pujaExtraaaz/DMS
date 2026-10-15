<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'permissions'])
        ->get()
        ->sortBy(function ($user) {
            $roleRank = [
                'super-admin'    => 1,
                'owner'         => 2,
                'sales-manager' => 3,
                'salesperson'   => 4,
                'warehouse'     => 5,
                'finance'       => 6,
                'driver'        => 7,
                'delivery-person' => 8,
            ][$user->roles->first()?->name] ?? 99;

            return sprintf('%02d_%s', $roleRank, strtolower(trim((string) $user->name)));
        });

        return view('users.index', compact('users'));
    }
}