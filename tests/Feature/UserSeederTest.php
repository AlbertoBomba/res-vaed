<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_both_users_with_their_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $customer = User::where('email', 'cliente@example.com')->firstOrFail();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('roles', 2);
        $this->assertTrue($admin->hasRole('company-admin', 'web'));
        $this->assertFalse($admin->hasRole('super-admin'));
        $this->assertTrue($customer->hasRole('customer', 'web'));
        $this->assertFalse($customer->hasRole('company-admin'));
        $this->assertNotNull($admin->email_verified_at);
        $this->assertNotNull($customer->email_verified_at);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue(Hash::check('password', $customer->password));
        $this->assertTrue(auth('web')->attempt([
            'email' => $admin->email,
            'password' => 'password',
        ]));
        auth('web')->logout();
        $this->assertTrue(auth('web')->attempt([
            'email' => $customer->email,
            'password' => 'password',
        ]));
    }

    public function test_reseeding_preserves_existing_data_passwords_and_other_roles(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $customer = User::where('email', 'cliente@example.com')->firstOrFail();
        $customerPassword = $customer->password;
        $admin->update(['name' => 'Nombre actualizado', 'password' => 'changed-test-password']);
        $admin->assignRole(Role::findOrCreate('another-role', 'web'));

        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('model_has_roles', 3);
        $this->assertSame('Nombre actualizado', $admin->fresh()->name);
        $this->assertTrue(Hash::check('changed-test-password', $admin->fresh()->password));
        $this->assertTrue($admin->fresh()->hasAllRoles(['company-admin', 'another-role']));
        $this->assertSame($customerPassword, $customer->fresh()->password);
    }
}
