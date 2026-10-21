@php
    $user = auth()->user();
    $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
    $roleLabel = $user->hasRole('super-admin') ? 'Superadministración' : ($isAdmin ? 'Administración' : 'Jugador');
@endphp

<x-app-layout>
    <section class="dashboard-intro">
        <div>
            <p class="workspace-eyebrow">{{ $isAdmin ? 'EL PULSO DE TU CLUB' : 'TU ESPACIO, TU RITMO' }}</p>
            <h1>Hola, {{ $user->name }}<span class="greeting-dot">.</span></h1>
            <p>{{ $isAdmin ? 'Todo empieza con una buena gestión. Este es tu punto de partida.' : 'Qué bien tenerte aquí. Haz sitio al deporte en tu día a día.' }}</p>
        </div>
        <div class="dashboard-date"><x-workspace-icon name="calendar" /><span>{{ now()->format('d / m / Y') }}</span></div>
    </section>

    <section class="dashboard-hero">
        <div class="hero-copy">
            <span class="hero-tag"><span class="status-dot"></span>{{ $isAdmin ? 'ESPACIO DE ADMINISTRACIÓN' : 'BIENVENIDO A VAED' }}</span>
            <h2>{{ $isAdmin ? 'Tu club, en un solo lugar.' : 'Menos pantallas. Más partidos.' }}</h2>
            <p>{{ $isAdmin ? 'Instalaciones, horarios y reservas. Estamos construyendo un espacio para que tú puedas centrarte en lo importante: tu comunidad.' : 'Tu próximo partido, tus reservas y toda tu información personal. Un espacio pensado para que solo te preocupes de disfrutar.' }}</p>
            <a href="{{ route('profile.show') }}" class="workspace-button button-light">Mi perfil <x-workspace-icon name="arrow" /></a>
        </div>
        <div class="hero-art" aria-hidden="true">
            <div class="hero-orbit orbit-one"></div><div class="hero-orbit orbit-two"></div>
            <div class="hero-court"><div class="court-line"></div><span></span></div>
            <div class="hero-ball"></div>
            <span class="hero-art-caption">PLAY YOUR WAY.</span>
        </div>
    </section>

    <nav class="workspace-tabs" aria-label="Secciones del panel">
        <a href="{{ route($isAdmin ? 'admin.dashboard' : 'customer.dashboard') }}" aria-current="page" class="is-active"><x-workspace-icon name="grid" />Vista general</a>
        <a href="{{ route('profile.show') }}"><x-workspace-icon name="user" />Mi perfil</a>
        <a href="{{ route('profile.show') }}#account-security"><x-workspace-icon name="shield" />Seguridad</a>
    </nav>

    <section class="dashboard-stats" aria-label="Estado de tu cuenta">
        <article class="stat-card">
            <div class="stat-icon icon-purple"><x-workspace-icon :name="$isAdmin ? 'court' : 'user'" /></div>
            <div><p>Tu espacio</p><h2>{{ $roleLabel }}</h2><span>{{ $isAdmin ? 'Panel de instalaciones' : 'Área personal privada' }}</span></div>
        </article>
        <article class="stat-card">
            <div class="stat-icon icon-mint"><x-workspace-icon name="mail" /></div>
            <div><p>Correo electrónico</p><h2>{{ $user->email_verified_at ? 'Verificado' : 'Sin verificar' }}</h2><span>Información de tu cuenta</span></div>
        </article>
        <article class="stat-card">
            <div class="stat-icon icon-peach"><x-workspace-icon name="shield" /></div>
            <div><p>Doble factor</p><h2>{{ $twoFactorEnabled ? 'Activado' : 'Sin activar' }}</h2><a href="{{ route('profile.show') }}#account-security">{{ $twoFactorEnabled ? 'Gestionar seguridad' : 'Proteger mi cuenta' }} <span aria-hidden="true">↗</span></a></div>
        </article>
        <article class="stat-card">
            <div class="stat-icon icon-blue"><x-workspace-icon name="calendar" /></div>
            <div><p>En VAED desde</p><h2>{{ $user->created_at->format('d/m/Y') }}</h2><span>El inicio de algo bueno</span></div>
        </article>
    </section>

    <div class="dashboard-columns">
        <section class="workspace-card dashboard-primary">
            <div class="card-heading">
                <div><h2>{{ $isAdmin ? 'Tu centro de operaciones' : 'Mis reservas' }}</h2><p>{{ $isAdmin ? 'Una nueva forma de gestionar tus instalaciones.' : 'Tus próximos planes deportivos, en un solo lugar.' }}</p></div>
                <span class="workspace-badge badge-purple">{{ $isAdmin ? 'Tu club' : 'Próximamente' }}</span>
            </div>
            @if ($isAdmin)
                <a href="{{ route('admin.courts.index') }}" class="dashboard-courts-link"><span class="stat-icon icon-purple"><x-workspace-icon name="court" /></span><span><strong>Gestionar instalaciones</strong><small>Crea pistas y configura sus características, horarios y tarifas.</small></span><x-workspace-icon name="arrow" /></a>
                <div class="module-grid">
                    @foreach ([
                        ['calendar', 'Reservas', 'La agenda de tu instalación.', 'icon-mint'],
                    ] as [$icon, $title, $description, $color])
                        <div class="module-preview">
                            <span class="stat-icon {{ $color }}"><x-workspace-icon :name="$icon" /></span>
                            <h3>{{ $title }}</h3><p>{{ $description }}</p>
                            <span class="module-status"><span></span> En preparación</span>
                        </div>
                    @endforeach
                </div>
                <div class="card-notice"><x-workspace-icon name="clock" /><p>El mantenimiento de pistas, horarios y tarifas ya está disponible. El módulo de reservas se incorporará próximamente.</p></div>
            @else
                <div class="booking-empty">
                    <div class="empty-illustration" aria-hidden="true"><x-workspace-icon name="calendar" /><span><x-workspace-icon name="ball" /></span></div>
                    <h3>Lo mejor está por jugar.</h3>
                    <p>Estamos preparando el sistema de reservas. Cuando esté disponible, aquí podrás consultar tus partidos y gestionar tus próximas citas con el deporte.</p>
                    <span class="empty-caption"><span class="status-dot"></span> El juego está a punto de empezar</span>
                </div>
            @endif
        </section>

        <aside class="dashboard-secondary" aria-label="Mi cuenta y accesos">
            <section class="workspace-card account-summary">
                <div class="card-heading"><h2>Mi cuenta</h2><a href="{{ route('profile.show') }}" class="text-link">Editar <span aria-hidden="true">↗</span></a></div>
                <div class="account-summary-person">
                    <span class="summary-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <h3>{{ $user->name }}</h3><p>{{ $user->email }}</p>
                    <span class="workspace-badge badge-purple">{{ $roleLabel }}</span>
                </div>
                <a class="account-detail-link" href="{{ route('profile.show') }}"><span><x-workspace-icon name="user" />Datos personales</span><x-workspace-icon name="arrow" /></a>
                <a class="account-detail-link" href="{{ route('profile.show') }}#account-security"><span><x-workspace-icon name="shield" />Contraseña y seguridad</span><x-workspace-icon name="arrow" /></a>
            </section>
            <a href="{{ url('/') }}" class="public-site-card">
                <span class="stat-icon"><x-workspace-icon name="external" /></span>
                <div><h2>Al otro lado de la pista</h2><p>Visita nuestra web pública</p></div>
                <x-workspace-icon name="arrow" />
            </a>
        </aside>
    </div>

    <section class="workspace-tip">
        <span class="tip-icon"><x-workspace-icon name="sparkles" /></span>
        <div><h2>Un pequeño paso, una cuenta más segura.</h2><p>{{ $twoFactorEnabled ? 'Ya tienes el doble factor activado. Recuerda guardar tus códigos de recuperación en un lugar seguro.' : 'Activa la autenticación en dos pasos desde tu perfil y añade una capa extra de protección.' }}</p></div>
        <a href="{{ route('profile.show') }}#account-security" class="text-link">Revisar seguridad <x-workspace-icon name="arrow" /></a>
    </section>
</x-app-layout>
