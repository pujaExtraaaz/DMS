<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SalespersonSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'salesperson',
            'guard_name' => 'web',
        ]);

        $defaultSalespersons = [
            'Kapil Dhir' => 'kapil.dhir@dms.test',
            'Abdul Majid' => 'abdul.majid@dms.test',
        ];

        foreach ($defaultSalespersons as $name => $email) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => bcrypt('password'),
                    'company_id' => 1,
                    'branch_id' => 1,
                ]
            );

            if (! $user->hasRole('salesperson')) {
                $user->assignRole($role);
            }
        }
    }
}
