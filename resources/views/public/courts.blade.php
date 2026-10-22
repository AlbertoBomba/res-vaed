<x-public-layout>
    <section class="public-hero">
        <div>
            <p class="workspace-eyebrow">TU TIEMPO. TU DEPORTE. TU PISTA.</p>
            <h1>Menos excusas.<br>Más <span>partidos.</span></h1>
            <p>Encuentra tu pista, elige cuándo jugar y haz sitio al deporte. Tu próximo partido empieza aquí.</p>
            <a href="#pistas" class="workspace-button court-primary">Encuentra tu pista <x-workspace-icon name="arrow" /></a>
        </div>
        <div class="public-hero-art" aria-hidden="true">
            <div class="public-court-lines"><span></span></div><div class="public-ball"></div>
            <span>PLAY YOUR WAY.</span>
        </div>
    </section>

    <section id="pistas" aria-labelledby="courts-heading">
        <div class="public-section-heading">
            <div><p class="workspace-eyebrow">HAZ HUECO PARA JUGAR</p><h2 id="courts-heading">Elige tu próximo plan.</h2></div>
            <p>Consulta libremente. Inicia sesión o regístrate para reservar.</p>
        </div>
        <form method="GET" action="{{ route('courts.index') }}#pistas" class="public-filters workspace-card" aria-label="Buscar disponibilidad">
            <div class="court-field">
                <label for="company_id">Club</label>
                <select id="company_id" name="company_id" class="court-input">
                    <option value="">Todos los clubes</option>
                    @foreach($companies as $company)<option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') == $company->id)>{{ $company->name }}</option>@endforeach
                </select>
            </div>
            <div class="court-field">
                <label for="sport">Deporte</label>
                <select id="sport" name="sport" class="court-input">
                    <option value="">Todos los deportes</option>
                    @foreach(config('courts.sports') as $value => $label)<option value="{{ $value }}" @selected(($filters['sport'] ?? '') === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="court-field"><label for="date">Día del partido</label><input id="date" type="date" name="date" value="{{ $date }}" min="{{ today()->toDateString() }}" required class="court-input"></div>
            <div class="court-field">
                <label for="duration">Duración</label>
                <select id="duration" name="duration" class="court-input">
                    @foreach(range(1, 24) as $hours)<option value="{{ $hours }}" @selected($duration === $hours)>{{ $hours }} {{ $hours === 1 ? 'hora' : 'horas' }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="workspace-button court-primary">Buscar pistas</button>
        </form>
        <div class="court-result-label" role="status"><span>{{ $courts->total() }} {{ $courts->total() === 1 ? 'pista encontrada' : 'pistas encontradas' }}</span><span>{{ \Carbon\CarbonImmutable::parse($date)->format('d/m/Y') }} · {{ $duration }} h</span></div>
        <div class="court-list-grid">
            @forelse($courts as $court)
                <article class="court-list-card workspace-card">
                    <div class="court-card-image">
                        @if($court->imageUrl())
                            <img src="{{ $court->imageUrl() }}" alt="{{ $court->name }}" loading="lazy">
                        @else
                            <div class="court-image-placeholder" aria-hidden="true"><x-workspace-icon name="court" /><span>EL ESPACIO PARA TU PRÓXIMO PARTIDO</span></div>
                        @endif
                        <span class="court-status court-status-active">{{ config('courts.sports.'.$court->sport) }}</span>
                    </div>
                    <div class="court-card-body">
                        <p class="court-card-sport">{{ $court->company->name }}</p>
                        <h3 class="public-court-name">{{ $court->name }}</h3>
                        <div class="court-tags">
                            <span>{{ config('courts.types.'.$court->type) }}</span>
                            <span>{{ config('courts.enclosures.'.$court->enclosure) }}</span>
                            @if($court->lighting)<span>Iluminación</span>@endif
                            @if($court->accessible)<span>Acceso adaptado</span>@endif
                        </div>
                        @if($court->description)<p class="public-court-description">{{ $court->description }}</p>@endif
                        @if($slots[$court->id])
                            <div class="public-price"><strong>{{ number_format($prices[$court->id] / 100, 2, ',', '.') }} €</strong><span>{{ $prices[$court->id] < 0 ? 'abono por ajuste de tarifa diaria' : 'por esta reserva' }} · {{ $duration }} h</span></div>
                            <p class="public-slot-label">Elige tu hora de inicio</p>
                            <div class="public-slots">
                                @foreach($slots[$court->id] as $hour)
                                    <a href="{{ route('bookings.create', ['court' => $court, 'date' => $date, 'start_hour' => $hour, 'duration' => $duration]) }}" aria-label="Reservar {{ $court->name }} a las {{ sprintf('%02d:00', $hour) }}">{{ sprintf('%02d:00', $hour) }}</a>
                                @endforeach
                            </div>
                        @else
                            <p class="public-no-slots">No quedan {{ $duration }} {{ $duration === 1 ? 'hora disponible' : 'horas seguidas disponibles' }} para este día. Prueba otra fecha o duración.</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="workspace-card court-empty"><x-workspace-icon name="court" /><h3>No hay pistas con estos filtros</h3><p>Prueba otro club o deporte. Las pistas en mantenimiento no están disponibles para reservar.</p><a href="{{ route('courts.index') }}#pistas" class="text-link">Ver todas las pistas</a></div>
            @endforelse
        </div>
        <div class="court-pagination">{{ $courts->links() }}</div>
        <p class="public-pricing-note">Horarios en {{ config('app.timezone') }}. Precios en euros. Las tarifas de 2 horas y de 3 o más horas se aplican al total de horas del mismo jugador, pista y día. El importe definitivo se comprueba al confirmar. No se realiza ningún cobro online.</p>
    </section>
    <section class="public-how" aria-label="Cómo reservar">
        <div><span>01</span><h2>Encuentra tu pista</h2><p>Tu club, tu deporte y el día que mejor te viene.</p></div>
        <div><span>02</span><h2>Elige tu horario</h2><p>Solo mostramos franjas libres para la duración elegida.</p></div>
        <div><span>03</span><h2>Reserva y juega</h2><p>Accede a tu cuenta o regístrate, revisa y confirma.</p></div>
    </section>
</x-public-layout>
