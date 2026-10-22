<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private AvailabilityService $availability,
        private CourtPricingService $pricing,
    ) {}

    public function quote(Court $court, User $user, string $date, int $duration): array
    {
        $previous = Booking::where('company_id', $court->company_id)->where('court_id', $court->id)
            ->where('user_id', $user->id)->whereDate('date', $date)->occupying()->get();

        return $this->pricing->quote(
            $court, $duration, (int) $previous->sum('duration_hours'), (int) $previous->sum('price_cents'),
        );
    }

    public function reserve(User $user, Court $court, array $data): Booking
    {
        return DB::transaction(function () use ($user, $court, $data) {
            // The court lock serializes availability checks and writes, including its tariff edits.
            $court = Court::where('company_id', $court->company_id)->whereKey($court->id)
                ->lockForUpdate()->firstOrFail();
            $hour = (int) $data['start_hour'];
            $duration = (int) $data['duration'];

            if (! in_array($hour, $this->availability->availableHours($court, $data['date'], $duration), true)) {
                throw ValidationException::withMessages([
                    'start_hour' => 'Este horario ya no está disponible. Elige otra franja.',
                ]);
            }

            $quote = $this->quote($court, $user, $data['date'], $duration);
            if ($quote['adjustment_cents'] !== (int) $data['quoted_price_cents']) {
                throw ValidationException::withMessages([
                    'price' => 'El precio ha cambiado. Revisa el nuevo importe antes de confirmar.',
                ]);
            }

            $start = CarbonImmutable::parse($data['date'])->startOfDay()->addHours($hour);

            return Booking::create([
                'company_id' => $court->company_id,
                'court_id' => $court->id,
                'user_id' => $user->id,
                'date' => $data['date'],
                'starts_at' => $start,
                'ends_at' => $start->addHours($duration),
                'duration_hours' => $duration,
                'price_cents' => $quote['adjustment_cents'],
                'status' => 'confirmed',
            ]);
        });
    }
}
