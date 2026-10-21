<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () {
            $accounts = [
                [
                    'name' => 'Administrador',
                    'email' => 'admin@example.com',
                    'role' => 'company-admin',
                    'password' => 'password',
                ],
                [
                    'name' => 'Usuario web',
                    'email' => 'cliente@example.com',
                    'role' => 'customer',
                    'password' => 'password',
                ],
            ];

            foreach ($accounts as $account) {
                $user = User::firstOrNew(['email' => $account['email']]);

                if (! $user->exists) {
                    $user->forceFill([
                        'name' => $account['name'],
                        'password' => $account['password'],
                        'email_verified_at' => now(),
                    ])->save();
                }

                $user->assignRole(Role::findOrCreate($account['role'], 'web'));
            }
        });

        $this->call(ClubCompanySeeder::class);
    }
}
