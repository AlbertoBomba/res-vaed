<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_either_panel(): void
    {
        foreach (['dashboard', 'admin.dashboard', 'customer.dashboard'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_administrator_is_redirected_to_the_management_panel(): void
    {
        $this->seed(UserSeeder::class);
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tu club, en un solo lugar.')
            ->assertSee('Instalaciones')
            ->assertSee('En preparación')
            ->assertDontSee('Mis reservas');
        $this->get(route('customer.dashboard'))->assertForbidden();
        $this->get(route('profile.show'))->assertOk()->assertSee('account-security');
    }

    public function test_customer_has_a_personal_panel_and_cannot_open_administration(): void
    {
        $this->seed(UserSeeder::class);
        $customer = User::where('email', 'cliente@example.com')->firstOrFail();

        $this->actingAs($customer)->get(route('dashboard'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Menos pantallas. Más partidos.')
            ->assertSee('Mis reservas')
            ->assertSee('Próximamente')
            ->assertDontSee('Tu centro de operaciones')
            ->assertDontSee('admin@example.com');
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('profile.show'))->assertOk()->assertSee('Tu perfil, tu espacio');
    }

    public function test_super_administrator_can_open_administration(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-admin', 'web'));

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->assertSee('Superadministración');
    }

    public function test_users_without_a_role_keep_access_to_their_personal_area(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.dashboard'))->assertOk()->assertDontSee('Panel de instalaciones');
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    #[DataProvider('loginAccounts')]
    public function test_both_test_accounts_can_log_in_through_fortify(string $email, string $destination): void
    {
        $this->seed(UserSeeder::class);
        $this->assertNotNull(RateLimiter::limiter('login'));

        $this->post(route('login'), ['email' => $email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::where('email', $email)->firstOrFail());
        $this->get('/dashboard')->assertRedirect(route($destination));
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public static function loginAccounts(): array
    {
        return [
            'administrator' => ['admin@example.com', 'admin.dashboard'],
            'customer' => ['cliente@example.com', 'customer.dashboard'],
        ];
    }
}
