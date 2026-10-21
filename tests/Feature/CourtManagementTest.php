<?php

namespace Tests\Feature;

use App\Livewire\Courts\Editor;
use App\Livewire\Courts\Index;
use App\Models\Company;
use App\Models\Court;
use App\Models\User;
use Database\Seeders\ClubCompanySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourtManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(UserSeeder::class);
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($this->admin);
    }

    private function fields(): array
    {
        return [
            'name' => 'Pista central', 'sport' => 'padel', 'type' => 'indoor',
            'surface' => 'artificial-grass', 'enclosure' => 'glass', 'status' => 'active',
            'capacity' => 4, 'lighting' => true, 'accessible' => true,
            'description' => 'Pista de cristal con acceso adaptado.',
            'hourly_price' => '20,50', 'two_hour_price' => '18', 'three_plus_hour_price' => '16.25',
            'weekly_hours' => [1 => ['0', '1', '23'], 2 => ['0', '12'], 3 => [], 4 => [], 5 => [], 6 => ['10'], 7 => []],
        ];
    }

    private function createCourt(): Court
    {
        Livewire::test(Editor::class)->set($this->fields())->call('save')->assertHasNoErrors()->assertRedirect(route('admin.courts.index'));

        return Court::latest('id')->firstOrFail();
    }

    public function test_it_creates_a_court_with_separate_sport_characteristics_prices_and_hours(): void
    {
        $court = $this->createCourt();
        $this->assertSame($this->admin->company_id, $court->company_id);
        $this->assertSame('padel', $court->sport);
        $this->assertSame('indoor', $court->type);
        $this->assertTrue($court->lighting);
        $this->assertTrue($court->accessible);
        $this->assertSame(2050, $court->hourly_price_cents);
        $this->assertSame(1800, $court->two_hour_price_cents);
        $this->assertSame(1625, $court->three_plus_hour_price_cents);
        $this->assertDatabaseCount('court_hours', 6);
        $this->assertDatabaseHas('court_hours', ['court_id' => $court->id, 'weekday' => 1, 'hour' => 23]);
        $this->assertDatabaseHas('court_hours', ['court_id' => $court->id, 'weekday' => 2, 'hour' => 0]);
        $this->get(route('admin.courts.index'))->assertOk()->assertSee('Pista central');
        $this->get(route('admin.courts.edit', $court))->assertOk()->assertSee('23:00')->assertSee('24:00');
    }

    public function test_it_updates_the_court_and_replaces_the_schedule_atomically(): void
    {
        $court = $this->createCourt();
        Livewire::test(Editor::class, ['court' => $court->id])
            ->set('name', 'Pista renovada')
            ->set('weekly_hours', [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => ['23']])
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Pista renovada', $court->fresh()->name);
        $this->assertSame(2, $court->fresh()->version);
        $this->assertDatabaseCount('court_hours', 1);
        $this->assertDatabaseHas('court_hours', ['court_id' => $court->id, 'weekday' => 7, 'hour' => 23]);
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_fields_do_not_create_a_partial_court(string $field, mixed $value): void
    {
        Livewire::test(Editor::class)->set($this->fields())->set($field, $value)->call('save')->assertHasErrors();
        $this->assertDatabaseCount('courts', 0);
        $this->assertDatabaseCount('court_hours', 0);
    }

    public static function invalidFields(): array
    {
        return [
            ['name', '   '], ['sport', 'unknown'], ['type', 'unknown'], ['status', 'unknown'],
            ['surface', 'unknown'], ['enclosure', 'unknown'], ['capacity', 0],
            ['hourly_price', '-1'], ['two_hour_price', '12.345'], ['three_plus_hour_price', '10000'],
            ['weekly_hours.1', ['24']], ['weekly_hours.1', ['-1']],
            ['weekly_hours.1', ['1', '1']], ['weekly_hours.1', ['1.5']],
            ['weekly_hours.8', ['1']], ['weekly_hours', [1 => ['1']]],
        ];
    }

    public function test_active_court_needs_hours_but_inactive_court_can_be_closed_all_week(): void
    {
        $empty = array_fill(1, 7, []);
        Livewire::test(Editor::class)->set($this->fields())->set('weekly_hours', $empty)
            ->call('save')->assertHasErrors('weekly_hours');
        $this->assertDatabaseCount('courts', 0);
        Livewire::test(Editor::class)->set($this->fields())->set('weekly_hours', $empty)->set('status', 'inactive')
            ->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('court_hours', 0);
    }

    public function test_image_can_be_uploaded_replaced_and_removed(): void
    {
        Livewire::test(Editor::class)->set($this->fields())
            ->set('image', UploadedFile::fake()->image('pista.jpg', 800, 600))
            ->call('save')->assertHasNoErrors();
        $court = Court::firstOrFail();
        $original = $court->image_path;
        Storage::disk('public')->assertExists($original);
        Livewire::test(Editor::class, ['court' => $court->id])
            ->set('image', UploadedFile::fake()->image('nueva.png', 800, 600))
            ->call('save')->assertHasNoErrors();
        Storage::disk('public')->assertMissing($original);
        $replacement = $court->fresh()->image_path;
        Storage::disk('public')->assertExists($replacement);
        Livewire::test(Editor::class, ['court' => $court->id])->set('remove_image', true)->call('save')->assertHasNoErrors();
        $this->assertNull($court->fresh()->image_path);
        Storage::disk('public')->assertMissing($replacement);
    }

    public function test_non_images_and_oversized_images_are_rejected(): void
    {
        Livewire::test(Editor::class)->set($this->fields())
            ->set('image', UploadedFile::fake()->create('script.php', 1, 'text/plain'))
            ->assertHasErrors('image')->call('save')->assertHasErrors('image');
        Livewire::test(Editor::class)->set($this->fields())
            ->set('image', UploadedFile::fake()->image('large.jpg')->size(5121))
            ->assertHasErrors('image');
        $this->assertDatabaseCount('courts', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles('courts'));
    }

    public function test_conflicting_edits_preserve_the_newer_values_and_remove_the_failed_upload(): void
    {
        $court = $this->createCourt();
        $editor = Livewire::test(Editor::class, ['court' => $court->id]);
        $court->forceFill(['name' => 'Cambio de otro gestor', 'version' => 2])->save();
        $editor->set('name', 'Cambio antiguo')->set('image', UploadedFile::fake()->image('old.jpg'))
            ->call('save')->assertHasErrors('conflict');
        $this->assertSame('Cambio de otro gestor', $court->fresh()->name);
        $this->assertDatabaseCount('court_hours', 6);
        $this->assertCount(0, Storage::disk('public')->allFiles('courts'));
    }

    public function test_another_company_cannot_read_modify_archive_or_restore_this_court(): void
    {
        $court = $this->createCourt();
        $otherCompany = Company::create(['name' => 'Otro club', 'slug' => 'otro-club']);
        $other = User::factory()->create(['company_id' => $otherCompany->id]);
        $other->assignRole('company-admin');
        auth()->forgetGuards();
        $this->actingAs($other, 'web');
        Livewire::test(Index::class)->assertViewHas('courts', fn ($courts) => $courts->total() === 0);
        $this->get(route('admin.courts.edit', $court))->assertNotFound();
        Livewire::test(Editor::class)->set($this->fields())->set('selected_company_id', $this->admin->company_id)
            ->call('save')->assertHasNoErrors();
        $this->assertSame($otherCompany->id, Court::latest('id')->firstOrFail()->company_id);
    }

    #[DataProvider('foreignActions')]
    public function test_foreign_court_ids_cannot_be_used_in_livewire_actions(string $action): void
    {
        $court = $this->createCourt();
        if ($action === 'restore') {
            $court->delete();
        }
        $company = Company::create(['name' => 'Otro club', 'slug' => 'otro']);
        $other = User::factory()->create(['company_id' => $company->id]);
        $other->assignRole('company-admin');
        auth()->forgetGuards();
        $this->actingAs($other, 'web');
        $this->expectException(ModelNotFoundException::class);
        Livewire::test(Index::class)->call($action, $court->id);
    }

    public static function foreignActions(): array
    {
        return [['confirmArchive'], ['restore']];
    }

    public function test_customer_and_guests_cannot_access_the_crud(): void
    {
        $court = $this->createCourt();
        $customer = User::where('email', 'cliente@example.com')->firstOrFail();
        $this->actingAs($customer);
        foreach (['admin.courts.index', 'admin.courts.create'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->get(route('admin.courts.edit', $court))->assertNotFound();
        Livewire::test(Index::class)->assertForbidden();
        Livewire::test(Editor::class)->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        auth()->guard('web')->logout();
        auth()->forgetGuards();
        $this->get(route('admin.courts.index'))->assertRedirect(route('login'));
    }

    public function test_archive_and_restore_preserve_hours_prices_and_image(): void
    {
        $court = $this->createCourt();
        Livewire::test(Index::class)->call('confirmArchive', $court->id)->call('archive')->assertHasNoErrors();
        $this->assertSoftDeleted($court);
        $this->assertDatabaseCount('court_hours', 6);
        $this->get(route('admin.courts.edit', $court))->assertNotFound();
        Livewire::test(Index::class)->set('status', 'archived')->assertSee('Pista central')
            ->call('restore', $court->id)->assertHasNoErrors();
        $this->assertNotSoftDeleted($court);
        $this->assertSame(2050, $court->fresh()->hourly_price_cents);
    }

    public function test_filters_and_week_copy_controls(): void
    {
        $court = $this->createCourt();
        Livewire::test(Index::class)->set('search', 'central')->assertViewHas('courts', fn ($courts) => $courts->total() === 1)
            ->set('sport', 'tenis')->assertViewHas('courts', fn ($courts) => $courts->total() === 0);
        Livewire::test(Editor::class)->call('selectDay', 7)->call('setDayHours', 'all')
            ->assertSet('weekly_hours.7', array_map('strval', range(0, 23)))
            ->call('copyDayToWeek')->assertSet('weekly_hours.1', array_map('strval', range(0, 23)))
            ->call('setDayHours', 'none')->assertSet('weekly_hours.7', []);
    }

    public function test_simulator_uses_accumulated_daily_hours_and_decimal_prices(): void
    {
        Livewire::test(Editor::class)->set($this->fields())->set('previewPreviousHours', 1)->set('previewNewHours', 1)
            ->call('previewPrice')->assertHasNoErrors()->assertSet('quote.daily_total_cents', 3600)
            ->assertSet('quote.adjustment_cents', 1550);
    }

    public function test_super_admin_can_select_an_existing_company_but_not_move_an_existing_court(): void
    {
        $court = $this->createCourt();
        $super = User::factory()->create();
        $super->assignRole(Role::findOrCreate('super-admin', 'web'));
        $this->actingAs($super);
        Livewire::test(Editor::class)->set($this->fields())->call('save')->assertHasErrors('selected_company_id');
        Livewire::test(Editor::class)->set($this->fields())->set('selected_company_id', $this->admin->company_id)
            ->call('save')->assertHasNoErrors();
        Livewire::test(Editor::class, ['court' => $court->id])->set('selected_company_id', 99999)
            ->call('save')->assertHasNoErrors();
        $this->assertSame($this->admin->company_id, $court->fresh()->company_id);
    }

    public function test_an_admin_without_a_company_cannot_create_and_reseeding_does_not_move_staff(): void
    {
        $other = Company::create(['name' => 'Empresa existente', 'slug' => 'existente']);
        $this->admin->company()->associate($other)->save();
        $this->seed(ClubCompanySeeder::class);
        $this->assertSame($other->id, $this->admin->fresh()->company_id);
        $this->assertNull(User::where('email', 'cliente@example.com')->firstOrFail()->company_id);
        $this->admin->company()->dissociate()->save();
        Livewire::test(Editor::class)->assertForbidden();
        $this->get(route('admin.courts.index'))->assertOk()->assertSee('no tiene una empresa asignada');
    }
}
