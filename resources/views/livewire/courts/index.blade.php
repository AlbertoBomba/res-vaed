<div>
    <section class="dashboard-intro">
        <div>
            <p class="workspace-eyebrow">EL ESPACIO DONDE TODO SUCEDE</p>
            <h1>Instalaciones<span class="greeting-dot">.</span></h1>
            <p>Pistas, horarios y precios. Cada detalle preparado para el próximo partido.</p>
        </div>
        @can('create', App\Models\Court::class)
            <a href="{{ route('admin.courts.create') }}" class="workspace-button court-primary"><span aria-hidden="true">+</span> Nueva pista</a>
        @endcan
    </section>

    @if(session('success'))<div class="court-alert alert-success" role="status"><x-workspace-icon name="check" />{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="court-alert alert-warning" role="alert">{{ session('warning') }}</div>@endif
    @if(! auth()->user()->company_id && ! auth()->user()->hasRole('super-admin'))
        <div class="court-alert alert-warning" role="alert">Tu cuenta no tiene una empresa asignada. Contacta con el administrador de la plataforma para poder crear pistas.</div>
    @endif

    <div class="court-overview">
        <div class="workspace-card court-summary"><span class="stat-icon icon-purple"><x-workspace-icon name="court" /></span><div><strong>{{ $total }}</strong><span>Pistas en tu espacio</span></div></div>
        <div class="workspace-card court-summary"><span class="stat-icon icon-mint"><x-workspace-icon name="check" /></span><div><strong>{{ $active }}</strong><span>Activas</span></div></div>
        <div class="workspace-card court-summary"><span class="stat-icon icon-peach"><x-workspace-icon name="clock" /></span><div><strong>{{ $maintenance }}</strong><span>En mantenimiento</span></div></div>
    </div>

    <section class="workspace-card court-filters" aria-label="Filtrar pistas">
        <x-court-field name="search" label="Buscar por nombre">
            <input id="search" type="search" wire:model.live.debounce.350ms="search" placeholder="Por ejemplo, Pista central" maxlength="100" class="court-input" />
        </x-court-field>
        <x-court-field name="sport" label="Deporte">
            <select id="sport" wire:model.live="sport" class="court-input"><option value="">Todos los deportes</option>@foreach(config('courts.sports') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        </x-court-field>
        <x-court-field name="status" label="Estado">
            <select id="status" wire:model.live="status" class="court-input"><option value="">Todas las pistas</option>@foreach(config('courts.statuses') as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach<option value="archived">Archivadas</option></select>
        </x-court-field>
    </section>

    @if($confirmingArchive)
        <div class="court-archive-confirm" role="alertdialog" aria-label="Confirmar archivo de pista">
            <div><strong>¿Archivar esta pista?</strong><p>Dejará de estar disponible. Conservaremos su imagen, horario y tarifas para que puedas recuperarla.</p></div>
            <div class="court-inline-actions">
                <button type="button" wire:click="$set('confirmingArchive', null)" class="workspace-button court-secondary">Cancelar</button>
                <button type="button" wire:click="archive" wire:loading.attr="disabled" class="workspace-button court-danger">Sí, archivar</button>
            </div>
        </div>
    @endif

    <div class="court-result-label" role="status"><span>{{ $courts->total() }} {{ $courts->total() === 1 ? 'pista encontrada' : 'pistas encontradas' }}</span><span wire:loading>Actualizando…</span></div>
    <div class="court-list-grid">
        @forelse($courts as $court)
            <article class="workspace-card court-list-card" wire:key="court-{{ $court->id }}">
                <div class="court-card-image">
                    @if($court->image_path)
                        <img src="{{ $court->imageUrl() }}" alt="{{ $court->name }}" loading="lazy" />
                    @else
                        <div class="court-image-placeholder"><x-workspace-icon name="court" /><span>El próximo partido empieza aquí</span></div>
                    @endif
                    <span @class(['court-status', 'court-status-active' => ! $court->trashed() && $court->status === 'active', 'court-status-maintenance' => ! $court->trashed() && $court->status === 'maintenance'])>{{ $court->trashed() ? 'Archivada' : config('courts.statuses.'.$court->status) }}</span>
                </div>
                <div class="court-card-body">
                    <p class="court-card-sport">{{ config('courts.sports.'.$court->sport) }} · {{ config('courts.types.'.$court->type) }}</p>
                    <h2>{{ $court->name }}</h2>
                    @if(auth()->user()->hasRole('super-admin'))<p class="field-hint">{{ $court->company->name }}</p>@endif
                    <div class="court-tags"><span>{{ config('courts.surfaces.'.$court->surface) }}</span><span>{{ $court->capacity }} personas</span>@if($court->lighting)<span>Iluminación</span>@endif</div>
                    <div class="court-card-hours"><x-workspace-icon name="clock" />{{ $court->hours_count }} franjas / semana</div>
                    <dl class="court-rate-strip">
                        @foreach([['1 h', $court->hourly_price_cents], ['2 h / día', $court->two_hour_price_cents], ['3+ h / día', $court->three_plus_hour_price_cents]] as [$duration, $cents])
                            <div><dt>{{ $duration }}</dt><dd>{{ number_format($cents / 100, 2, ',', '.') }} <small>€/h</small></dd></div>
                        @endforeach
                    </dl>
                </div>
                <div class="court-card-footer">
                    @if($court->trashed())
                        <button type="button" wire:click="restore({{ $court->id }})" wire:loading.attr="disabled" class="text-link">Recuperar pista <x-workspace-icon name="arrow" /></button>
                    @else
                        <a href="{{ route('admin.courts.edit', $court) }}" class="text-link">Editar pista <x-workspace-icon name="arrow" /></a>
                        <button type="button" wire:click="confirmArchive({{ $court->id }})" class="court-archive-button" aria-label="Archivar {{ $court->name }}">Archivar</button>
                    @endif
                </div>
            </article>
        @empty
            <div class="workspace-card court-empty">
                <span class="stat-icon icon-purple"><x-workspace-icon name="court" /></span><h2>{{ $search || $sport || $status ? 'No hay pistas con estos filtros' : 'Dale un lugar al próximo partido' }}</h2>
                <p>{{ $search || $sport || $status ? 'Prueba otro nombre, deporte o estado.' : 'Crea tu primera pista y configura sus características, horarios y tarifas.' }}</p>
                @can('create', App\Models\Court::class)<a href="{{ route('admin.courts.create') }}" class="workspace-button court-primary">Crear pista <x-workspace-icon name="arrow" /></a>@endcan
            </div>
        @endforelse
    </div>
    <div class="court-pagination">{{ $courts->links() }}</div>
</div>
