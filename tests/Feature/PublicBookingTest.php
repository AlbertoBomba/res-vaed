<?php

namespace Tests\Feature;

use App\Actions\Jetstream\DeleteUser;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    private Court $court;

    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-21 08:30:00'));
        $company = Company::create(['name' => 'Club de prueba', 'slug' => 'club-prueba']);
        $this->court = $this->makeCourt($company, 'Pista pública');
        $this->player = User::factory()->create();
    }

    private function makeCourt(Company $company, string $name): Court
    {
        $court = new Court([
            'name' => $name, 'sport' => 'padel', 'type' => 'indoor',
            'surface' => 'artificial-grass', 'enclosure' => 'glass', 'status' => 'active',
            'hourly_price_cents' => 2000, 'two_hour_price_cents' => 1800,
            'three_plus_hour_price_cents' => 1600,
        ]);
        $court->company_id = $company->id;
        $court->save();
        $court->hours()->createMany(array_map(fn ($hour) => ['weekday' => 3, 'hour' => $hour], [8, 9, 10, 11, 12, 14, 23]));

        return $court;
    }

    private function selection(array $overrides = []): array
    {
        return array_replace([
            'date' => '2026-10-21', 'start_hour' => 9, 'duration' => 1,
            'quoted_price_cents' => 2000,
        ], $overrides);
    }

    private function confirmationUrl(array $overrides = []): string
    {
        return route('bookings.create', ['court' => $this->court] + $this->selection($overrides));
    }

    public function test_public_catalogue_shows_only_active_unarchived_courts_and_filters_by_club_and_sport(): void
    {
        $other = Company::create(['name' => 'Otro club', 'slug' => 'otro']);
        $this->makeCourt($other, 'Otra pista');
        $maintenance = $this->makeCourt($other, 'En mantenimiento');
        $maintenance->update(['status' => 'maintenance']);
        $archived = $this->makeCourt($other, 'Archivada');
        $archived->delete();
        $this->get(route('courts.index'))->assertOk()
            ->assertSee('Pista pública')->assertSee('Otra pista')->assertDontSee('Archivada')
            ->assertViewHas('courts', fn ($courts) => $courts->total() === 2);
        $this->get(route('courts.index', ['company_id' => $this->court->company_id]))
            ->assertOk()->assertSee('Pista pública')->assertDontSee('Otra pista');
        $this->get(route('courts.index', ['sport' => 'tenis']))->assertOk()->assertSee('No hay pistas con estos filtros');
    }

    public function test_guests_are_sent_to_registration_and_cannot_create_or_read_bookings(): void
    {
        $this->get($this->confirmationUrl())->assertRedirect(route('register'))
            ->assertSessionHas('url.intended', route('bookings.create', [
                'court' => $this->court, 'date' => '2026-10-21', 'start_hour' => 9, 'duration' => 1,
            ]));
        $this->get(route('register'))->assertOk()->assertSee('Crea tu cuenta para reservar.');
        $this->post(route('bookings.store', $this->court), $this->selection())->assertRedirect(route('login'));
        $this->get(route('bookings.index'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_registration_assigns_customer_and_returns_to_confirmation_without_auto_booking(): void
    {
        $this->get($this->confirmationUrl());
        $this->post(route('register'), [
            'name' => 'Nueva jugadora', 'email' => 'nueva@example.com',
            'password' => 'test-password-123', 'password_confirmation' => 'test-password-123',
            'terms' => true,
        ])->assertRedirect(route('bookings.create', [
            'court' => $this->court, 'date' => '2026-10-21', 'start_hour' => 9, 'duration' => 1,
        ]));
        $this->assertAuthenticated();
        $this->assertTrue(User::where('email', 'nueva@example.com')->firstOrFail()->hasRole('customer'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_login_preserves_the_selected_slot(): void
    {
        $this->get($this->confirmationUrl());
        $this->post(route('login'), ['email' => $this->player->email, 'password' => 'password'])
            ->assertRedirect(route('bookings.create', [
                'court' => $this->court, 'date' => '2026-10-21', 'start_hour' => 9, 'duration' => 1,
            ]));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_authenticated_player_confirms_with_server_owned_company_user_and_price(): void
    {
        $this->actingAs($this->player)->get($this->confirmationUrl())->assertOk()->assertSee('Confirmar reserva');
        $this->assertDatabaseCount('bookings', 0);
        $this->post(route('bookings.store', $this->court), $this->selection([
            'user_id' => 999, 'company_id' => 999, 'price_cents' => 1, 'status' => 'cancelled',
        ]))->assertRedirect(route('bookings.index'));
        $this->assertDatabaseHas('bookings', [
            'court_id' => $this->court->id, 'company_id' => $this->court->company_id,
            'user_id' => $this->player->id, 'price_cents' => 2000, 'status' => 'confirmed',
            'starts_at' => '2026-10-21 09:00:00', 'ends_at' => '2026-10-21 10:00:00',
        ]);
        $this->post(route('bookings.store', $this->court), $this->selection())
            ->assertSessionHasErrors('start_hour');
        $this->assertDatabaseCount('bookings', 1);
        $this->get($this->confirmationUrl())->assertOk()->assertDontSee('Confirmar reserva')->assertSee('ya no está disponible');
    }

    public function test_overlapping_bookings_are_rejected_but_adjacent_ones_are_allowed_and_repriced(): void
    {
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection(['duration' => 2, 'quoted_price_cents' => 3600]))
            ->assertSessionHasNoErrors();
        $this->post(route('bookings.store', $this->court), $this->selection(['start_hour' => 10]))
            ->assertSessionHasErrors('start_hour');
        $this->post(route('bookings.store', $this->court), $this->selection(['start_hour' => 11, 'quoted_price_cents' => 1200]))
            ->assertSessionHasNoErrors();
        $this->assertSame(4800, (int) Booking::sum('price_cents'));
    }

    public function test_blocks_cover_partial_hours_and_can_cross_midnight(): void
    {
        CourtBlock::create([
            'company_id' => $this->court->company_id, 'court_id' => $this->court->id,
            'starts_at' => '2026-10-20 23:00:00', 'ends_at' => '2026-10-21 09:30:00',
        ]);
        CourtBlock::create([
            'company_id' => $this->court->company_id, 'court_id' => $this->court->id,
            'starts_at' => '2026-10-21 11:15:00', 'ends_at' => '2026-10-21 12:00:00',
        ]);
        $this->assertSame([10, 12, 14, 23], app(AvailabilityService::class)->availableHours($this->court, '2026-10-21', 1));
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection())
            ->assertSessionHasErrors('start_hour');
        $this->post(route('bookings.store', $this->court), $this->selection(['start_hour' => 10, 'duration' => 2]))
            ->assertSessionHasErrors('start_hour');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_closed_past_and_non_contiguous_hours_are_rejected(): void
    {
        $this->actingAs($this->player);
        foreach ([['start_hour' => 8], ['start_hour' => 13], ['start_hour' => 12, 'duration' => 2], ['start_hour' => 23, 'duration' => 2]] as $selection) {
            $this->post(route('bookings.store', $this->court), $this->selection($selection))->assertSessionHasErrors('start_hour');
        }
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_reservation_can_end_at_midnight(): void
    {
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection(['start_hour' => 23]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', ['ends_at' => '2026-10-22 00:00:00']);
        $this->get(route('bookings.index'))->assertOk()->assertSee('23:00 – 24:00');
    }

    public function test_cancelled_bookings_do_not_occupy_or_affect_the_daily_price(): void
    {
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection());
        Booking::firstOrFail()->update(['status' => 'cancelled']);
        $this->post(route('bookings.store', $this->court), $this->selection())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_pending_bookings_occupy_a_slot_even_for_another_player(): void
    {
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection());
        Booking::firstOrFail()->update(['status' => 'pending']);
        $this->actingAs(User::factory()->create())->post(route('bookings.store', $this->court), $this->selection())
            ->assertSessionHasErrors('start_hour');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_other_players_do_not_affect_the_daily_tariff(): void
    {
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection());
        $this->actingAs(User::factory()->create())
            ->post(route('bookings.store', $this->court), $this->selection(['start_hour' => 10]))
            ->assertSessionHasNoErrors();
        $this->assertSame([2000, 2000], Booking::orderBy('id')->pluck('price_cents')->all());
    }

    public function test_account_deletion_keeps_reservations_without_blocking_jetstream(): void
    {
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection());
        app(DeleteUser::class)->delete($this->player);
        $this->assertNull($this->player->fresh());
        $this->assertDatabaseHas('bookings', ['court_id' => $this->court->id, 'user_id' => null]);
        $this->assertNotContains(9, app(AvailabilityService::class)->availableHours($this->court, '2026-10-21', 1));
    }

    public function test_public_search_rejects_invalid_filters(): void
    {
        foreach ([
            ['date' => '2026-10-20'], ['date' => 'not-a-date'], ['duration' => 25],
            ['sport' => 'invalid'], ['company_id' => 999],
        ] as $filters) {
            $this->from(route('courts.index'))->get(route('courts.index', $filters))
                ->assertRedirect(route('courts.index'))->assertSessionHasErrors(array_key_first($filters));
        }
    }

    public function test_maintenance_and_archived_courts_cannot_be_reserved(): void
    {
        $this->actingAs($this->player);
        $this->court->update(['status' => 'maintenance']);
        $this->get($this->confirmationUrl())->assertNotFound();
        $this->post(route('bookings.store', $this->court), $this->selection())->assertSessionHasErrors('start_hour');
        $this->court->delete();
        $this->post(route('bookings.store', $this->court), $this->selection())->assertNotFound();
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_price_changes_require_review_and_negative_adjustments_are_explicit(): void
    {
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection());
        $this->court->update(['two_hour_price_cents' => 500]);
        $this->post(route('bookings.store', $this->court), $this->selection(['start_hour' => 10]))
            ->assertSessionHasErrors('price');
        $this->get($this->confirmationUrl(['start_hour' => 10]))->assertOk()
            ->assertSee('Abono por ajuste')->assertSee('-10,00');
        $this->post(route('bookings.store', $this->court), $this->selection(['start_hour' => 10, 'quoted_price_cents' => -1000]))
            ->assertSessionHasNoErrors();
        $this->assertSame(1000, (int) Booking::sum('price_cents'));
    }

    public function test_history_is_private_and_remains_readable_after_court_archival(): void
    {
        $other = Company::create(['name' => 'Club visitante', 'slug' => 'visitante']);
        $otherCourt = $this->makeCourt($other, 'Pista visitante');
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection());
        $this->post(route('bookings.store', $otherCourt), $this->selection())->assertSessionHasNoErrors();
        $this->court->delete();
        $this->get(route('bookings.index'))->assertOk()->assertSee('Pista pública')->assertSee('Pista visitante');
        $this->actingAs(User::factory()->create())->get(route('bookings.index', ['user_id' => $this->player->id]))
            ->assertOk()->assertDontSee('Pista pública')->assertDontSee('Pista visitante')
            ->assertSee('Todavía no tienes reservas');
    }

    #[DataProvider('invalidSelections')]
    public function test_invalid_reservations_are_validated(array $data, string $field): void
    {
        $this->actingAs($this->player)->post(route('bookings.store', $this->court), $this->selection($data))
            ->assertSessionHasErrors($field);
        $this->assertDatabaseCount('bookings', 0);
    }

    public static function invalidSelections(): array
    {
        return [
            [['date' => '2026-10-20'], 'date'],
            [['date' => 'not-a-date'], 'date'],
            [['date' => '2026-02-30'], 'date'],
            [['duration' => 0], 'duration'],
            [['duration' => 25], 'duration'],
            [['duration' => '1.5'], 'duration'],
            [['start_hour' => 24], 'start_hour'],
            [['quoted_price_cents' => null], 'quoted_price_cents'],
        ];
    }
}
