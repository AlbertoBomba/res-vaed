<x-public-layout title="Confirma tu reserva">
    <a href="{{ route('courts.index', ['company_id' => $court->company_id, 'date' => $selection['date'], 'duration' => $selection['duration']]) }}#pistas" class="court-back-link">← Volver a los horarios</a>
    <section class="public-confirm workspace-card">
        <p class="workspace-eyebrow">YA CASI ESTÁS EN LA PISTA</p>
        <h1>Tu próximo partido.</h1>
        <p class="public-confirm-club">{{ $court->company->name }} · {{ config('courts.sports.'.$court->sport) }}</p>
        <h2>{{ $court->name }}</h2>
        <dl class="public-booking-details">
            <div><dt>Día</dt><dd>{{ \Carbon\CarbonImmutable::parse($selection['date'])->format('d/m/Y') }}</dd></div>
            <div><dt>Horario ({{ config('app.timezone') }})</dt><dd>{{ sprintf('%02d:00', $selection['start_hour']) }} – {{ sprintf('%02d:00', (int) $selection['start_hour'] + (int) $selection['duration']) }}</dd></div>
            <div><dt>Duración</dt><dd>{{ $selection['duration'] }} h</dd></div>
            <div><dt>Jugador</dt><dd>{{ auth()->user()->name }}</dd></div>
        </dl>
        @if($available)
            <div class="public-confirm-price">
                <span>{{ $quote['adjustment_cents'] < 0 ? 'Abono por ajuste de tarifa diaria' : 'Importe de esta reserva' }}</span>
                <strong>{{ number_format($quote['adjustment_cents'] / 100, 2, ',', '.') }} €</strong>
            </div>
            <p class="public-pricing-note">Total del día en esta pista: {{ $quote['hours'] }} h · {{ number_format($quote['daily_total_cents'] / 100, 2, ',', '.') }} €. El importe incluye el ajuste de las reservas anteriores del mismo día. No se realiza ningún cobro online.</p>
            <form method="POST" action="{{ route('bookings.store', $court) }}">
                @csrf
                @foreach($selection as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <input type="hidden" name="quoted_price_cents" value="{{ $quote['adjustment_cents'] }}">
                <button type="submit" class="workspace-button court-primary public-confirm-button">Confirmar reserva <x-workspace-icon name="arrow" /></button>
            </form>
            <p class="public-pricing-note">La franja no queda reservada hasta que confirmes. Si alguien se adelanta, te avisaremos para que elijas otra.</p>
        @else
            <div role="alert" class="court-alert alert-warning">Este horario ya no está disponible. Vuelve al catálogo para elegir otra franja.</div>
        @endif
    </section>
</x-public-layout>
