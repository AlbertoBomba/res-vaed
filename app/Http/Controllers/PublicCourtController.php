<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchCourtsRequest;
use App\Models\Company;
use App\Models\Court;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\CourtPricingService;
use Illuminate\Contracts\View\View;

class PublicCourtController extends Controller
{
    public function index(
        SearchCourtsRequest $request,
        AvailabilityService $availability,
        BookingService $bookings,
        CourtPricingService $pricing,
    ): View {
        $filters = $request->validated();
        $date = $filters['date'];
        $duration = (int) $filters['duration'];
        $courts = Court::with(['company', 'hours'])->where('status', 'active')
            ->when($filters['company_id'] ?? null, fn ($query, $company) => $query->forCompany((int) $company))
            ->when($filters['sport'] ?? null, fn ($query, $sport) => $query->where('sport', $sport))
            ->orderBy('name')->orderBy('id')->paginate(9)->withQueryString();
        $slots = [];
        $prices = [];

        foreach ($courts as $court) {
            $slots[$court->id] = $availability->availableHours($court, $date, $duration);
            if ($slots[$court->id]) {
                $prices[$court->id] = $request->user()
                    ? $bookings->quote($court, $request->user(), $date, $duration)['adjustment_cents']
                    : $pricing->dailyTotal($court, $duration);
            }
        }

        return view('public.courts', [
            'courts' => $courts,
            'companies' => Company::whereHas('courts', fn ($query) => $query->where('status', 'active'))->orderBy('name')->get(),
            'filters' => $filters,
            'date' => $date,
            'duration' => $duration,
            'slots' => $slots,
            'prices' => $prices,
        ]);
    }
}
