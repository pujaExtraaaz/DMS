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
            return [
                'super-admin'    => 1,
                'owner'         => 2,
                'sales-manager' => 3,
                'salesperson'   => 4,
                'warehouse'     => 5,
                'finance'       => 6,
                'driver'        => 7,
                'delivery-person' => 8,
            ][$user->roles->first()?->name] ?? 99;
        });

        return view('users.index', compact('users'));
    }
}