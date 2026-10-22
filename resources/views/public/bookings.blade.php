<x-public-layout title="Mis reservas">
    <div class="public-section-heading">
        <div><p class="workspace-eyebrow">TU AGENDA DEPORTIVA</p><h1>Mis reservas.</h1></div>
        <a href="{{ route('courts.index') }}#pistas" class="workspace-button court-primary">Reservar otra pista <x-workspace-icon name="arrow" /></a>
    </div>
    <div class="public-booking-list">
        @forelse($bookings as $booking)
            <article class="workspace-card public-booking-row">
                <div>
                    <span class="court-card-sport">{{ $booking->company->name }} · Reserva #{{ $booking->id }}</span>
                    <h2>{{ $booking->court->name }}</h2>
                    <p>{{ $booking->date->format('d/m/Y') }} · {{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->isSameDay($booking->starts_at) ? $booking->ends_at->format('H:i') : '24:00' }} ({{ config('app.timezone') }}) · {{ $booking->duration_hours }} h</p>
                </div>
                <div class="public-booking-row-end">
                    <span class="workspace-badge badge-purple">{{ ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada'][$booking->status] ?? $booking->status }}</span>
                    <strong>{{ number_format($booking->price_cents / 100, 2, ',', '.') }} €</strong>
                    @if($booking->price_cents < 0)<small>Abono por ajuste de tarifa diaria</small>@endif
                </div>
            </article>
        @empty
            <div class="workspace-card court-empty"><x-workspace-icon name="calendar" /><h2>Lo mejor está por jugar.</h2><p>Todavía no tienes reservas. Encuentra tu pista y prepara tu próximo partido.</p><a href="{{ route('courts.index') }}#pistas" class="workspace-button court-primary">Explorar pistas</a></div>
        @endforelse
    </div>
    <div class="court-pagination">{{ $bookings->links() }}</div>
</x-public-layout>
