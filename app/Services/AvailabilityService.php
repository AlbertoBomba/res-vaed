<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtBlock;
use Carbon\CarbonImmutable;

class AvailabilityService
{
    /** @return list<int> */
    public function availableHours(Court $court, string $date, int $duration): array
    {
        if ($court->trashed() || $court->status !== 'active' || $duration < 1 || $duration > 24) {
            return [];
        }

        $day = CarbonImmutable::parse($date)->startOfDay();
        $now = CarbonImmutable::now();
        $court->loadMissing('hours');
        $openingHours = $court->hours->where('weekday', $day->isoWeekday())->pluck('hour')->all();
        $bookings = Booking::where('company_id', $court->company_id)->where('court_id', $court->id)
            ->occupying()->where('starts_at', '<', $day->addDay())->where('ends_at', '>', $day)->get();
        $blocks = CourtBlock::where('company_id', $court->company_id)->where('court_id', $court->id)
            ->where('starts_at', '<', $day->addDay())->where('ends_at', '>', $day)->get();
        $occupied = $bookings->concat($blocks);
        $available = [];

        foreach (range(0, 24 - $duration) as $hour) {
            $start = $day->addHours($hour);
            $end = $start->addHours($duration);

            if ($start->lte($now) || array_diff(range($hour, $hour + $duration - 1), $openingHours)) {
                continue;
            }

            if ($occupied->contains(fn ($record) => $record->starts_at->lt($end) && $record->ends_at->gt($start))) {
                continue;
            }

            $available[] = $hour;
        }

        return $available;
    }
}
