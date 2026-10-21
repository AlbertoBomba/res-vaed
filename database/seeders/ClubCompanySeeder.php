<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClubCompanySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admin = User::where('email', 'admin@example.com')->lockForUpdate()->first();

            if (! $admin || ! $admin->hasRole('company-admin')) {
                throw new RuntimeException('Debe existir el administrador de pruebas con el rol company-admin antes de asignarle Club VAED.');
            }

            if ($admin->company_id === null) {
                $company = Company::firstOrCreate(['slug' => 'club-vaed'], ['name' => 'Club VAED']);
                $admin->company()->associate($company)->save();
            }
        });
    }
}
