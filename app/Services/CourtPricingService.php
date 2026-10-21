<?php

namespace App\Services;

use App\Models\Court;
use InvalidArgumentException;

class CourtPricingService
{
    public function dailyTotal(Court $court, int $hours): int
    {
        if ($hours < 0 || $hours > 24) {
            throw new InvalidArgumentException('Las horas diarias deben estar entre 0 y 24.');
        }

        $rate = match (true) {
            $hours >= 3 => $court->three_plus_hour_price_cents,
            $hours === 2 => $court->two_hour_price_cents,
            default => $court->hourly_price_cents,
        };

        return $hours * $rate;
    }

    /**
     * $alreadyChargedCents must come from persisted charges for the same customer, court and date.
     * A negative adjustment is credit due, not an amount to silently clamp to zero.
     */
    public function quote(Court $court, int $newHours, int $previousHours = 0, int $alreadyChargedCents = 0): array
    {
        if ($newHours < 1 || $previousHours < 0 || $alreadyChargedCents < 0 || $newHours + $previousHours > 24) {
            throw new InvalidArgumentException('La duración y el importe anterior no son válidos.');
        }

        $hours = $newHours + $previousHours;
        $total = $this->dailyTotal($court, $hours);

        return [
            'hours' => $hours,
            'hourly_rate_cents' => intdiv($total, $hours),
            'daily_total_cents' => $total,
            'adjustment_cents' => $total - $alreadyChargedCents,
        ];
    }
}
