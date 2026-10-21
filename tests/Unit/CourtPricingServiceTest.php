<?php

namespace Tests\Unit;

use App\Models\Court;
use App\Services\CourtPricingService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourtPricingServiceTest extends TestCase
{
    #[DataProvider('dailyPrices')]
    public function test_daily_total_uses_one_rate_for_all_accumulated_hours(int $hours, int $expected): void
    {
        $court = new Court(['hourly_price_cents' => 2000, 'two_hour_price_cents' => 1800, 'three_plus_hour_price_cents' => 1600]);
        $this->assertSame($expected, (new CourtPricingService)->dailyTotal($court, $hours));
    }

    public static function dailyPrices(): array
    {
        return [[0, 0], [1, 2000], [2, 3600], [3, 4800], [4, 6400], [24, 38400]];
    }

    public function test_additional_hours_deduct_actual_previous_charges_not_the_current_rate(): void
    {
        $court = new Court(['hourly_price_cents' => 2000, 'two_hour_price_cents' => 1800, 'three_plus_hour_price_cents' => 1600]);
        $pricing = new CourtPricingService;
        $this->assertSame(['hours' => 2, 'hourly_rate_cents' => 1800, 'daily_total_cents' => 3600, 'adjustment_cents' => 1600], $pricing->quote($court, 1, 1, 2000));
        $this->assertSame(1200, $pricing->quote($court, 1, 2, 3600)['adjustment_cents']);
        $this->assertSame(1800, $pricing->quote($court, 1, 2, 3000)['adjustment_cents']);
    }

    public function test_a_large_discount_returns_a_credit_explicitly(): void
    {
        $court = new Court(['hourly_price_cents' => 2000, 'two_hour_price_cents' => 500, 'three_plus_hour_price_cents' => 0]);
        $this->assertSame(-1000, (new CourtPricingService)->quote($court, 1, 1, 2000)['adjustment_cents']);
        $this->assertSame(0, (new CourtPricingService)->dailyTotal($court, 24));
    }

    #[DataProvider('invalidDurations')]
    public function test_invalid_quote_inputs_are_rejected(int $added, int $previous, int $paid): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CourtPricingService)->quote(new Court, $added, $previous, $paid);
    }

    public static function invalidDurations(): array
    {
        return [[0, 0, 0], [1, -1, 0], [1, 0, -1], [1, 24, 0], [25, 0, 0]];
    }
}
