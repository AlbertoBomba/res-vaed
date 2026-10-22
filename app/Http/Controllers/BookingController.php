<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReserveCourtRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function create(
        ReserveCourtRequest $request,
        Court $court,
        AvailabilityService $availability,
        BookingService $bookings,
    ): View|RedirectResponse {
        abort_unless($court->status === 'active', 404);
        $selection = $request->safe()->only(['date', 'start_hour', 'duration']);

        if (! $request->user()) {
            $request->session()->put('url.intended', route('bookings.create', ['court' => $court] + $selection));

            return to_route('register')->with('status', 'Crea tu cuenta para reservar. Si ya tienes una, inicia sesión. Conservaremos el horario que has elegido.');
        }

        $available = in_array(
            (int) $selection['start_hour'],
            $availability->availableHours($court, $selection['date'], (int) $selection['duration']),
            true,
        );

        return view('public.confirm', [
            'court' => $court->load('company'),
            'selection' => $selection,
            'available' => $available,
            'quote' => $available ? $bookings->quote($court, $request->user(), $selection['date'], (int) $selection['duration']) : null,
        ]);
    }

    public function store(ReserveCourtRequest $request, Court $court, BookingService $bookings): RedirectResponse
    {
        $booking = $bookings->reserve($request->user(), $court, $request->validated());

        return to_route('bookings.index')->with('status', 'Reserva #'.$booking->id.' confirmada. ¡Nos vemos en la pista!');
    }

    public function index(Request $request): View
    {
        return view('public.bookings', [
            'bookings' => Booking::where('user_id', $request->user()->id)->with(['company', 'court'])
                ->orderByDesc('starts_at')->orderByDesc('id')->paginate(10),
        ]);
    }
}
