@php
    $isAdmin = auth()->user()->isAdministrator();
    $homeRoute = $isAdmin ? 'admin.dashboard' : 'customer.dashboard';
    $onDashboard = request()->routeIs('admin.dashboard', 'customer.dashboard');
    $roleLabel = auth()->user()->hasRole('super-admin') ? 'Superadministración' : ($isAdmin ? 'Administración' : 'Área personal');
    $upcoming = $isAdmin
        ? [['court', 'Instalaciones'], ['calendar', 'Reservas'], ['clock', 'Horarios'], ['wallet', 'Tarifas']]
        : [['calendar', 'Mis reservas'], ['court', 'Explorar pistas']];
@endphp

<div>
    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="workspace-overlay" @click="sidebarOpen = false" aria-hidden="true"></div>

    <aside id="workspace-sidebar" class="workspace-sidebar" :class="{ 'is-open': sidebarOpen }" x-trap.inert.noscroll="sidebarOpen" aria-label="Menú principal">
        <a href="{{ route($homeRoute) }}" class="workspace-brand" aria-label="VAED - Inicio">
            <span class="brand-symbol"><x-workspace-icon name="court" /></span>
            <span><strong>vaed<span class="text-violet-300">.</span></strong><small>sport & community</small></span>
        </a>
        <button type="button" class="sidebar-close icon-button" @click="sidebarOpen = false" aria-label="Cerrar menú">
            <x-workspace-icon name="close" />
        </button>

        <div class="sidebar-space">
            <span class="space-symbol"><x-workspace-icon :name="$isAdmin ? 'court' : 'ball'" /></span>
            <div><strong>{{ $isAdmin ? 'Gestión deportiva' : 'Mi espacio deportivo' }}</strong><small>{{ $roleLabel }}</small></div>
            <span class="status-dot"></span>
        </div>

        <nav class="sidebar-navigation" aria-label="Navegación">
            <p class="sidebar-label">TU ESPACIO</p>
            <a href="{{ route($homeRoute) }}" @class(['sidebar-link', 'is-active' => $onDashboard]) @if($onDashboard) aria-current="page" @endif>
                <x-workspace-icon name="grid" /><span>Vista general</span>
            </a>
            @foreach ($upcoming as [$icon, $label])
                <span class="sidebar-link is-disabled" aria-disabled="true" title="Esta sección estará disponible próximamente">
                    <x-workspace-icon :name="$icon" /><span>{{ $label }}</span><small>Pronto</small>
                </span>
            @endforeach

            <p class="sidebar-label sidebar-label-spaced">MI CUENTA</p>
            <a href="{{ route('profile.show') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('profile.show')]) @if(request()->routeIs('profile.show')) aria-current="page" @endif>
                <x-workspace-icon name="user" /><span>Perfil y seguridad</span>
            </a>
            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                <a href="{{ route('api-tokens.index') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('api-tokens.*')])>
                    <x-workspace-icon name="key" /><span>Tokens API</span>
                </a>
            @endif
            <a href="{{ url('/') }}" class="sidebar-link">
                <x-workspace-icon name="external" /><span>Web pública</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-note">
                <span class="sidebar-note-icon"><x-workspace-icon name="sparkles" /></span>
                <strong>{{ $isAdmin ? 'Más club. Menos gestión.' : 'Tu tiempo, tu deporte.' }}</strong>
                <p>{{ $isAdmin ? 'Un espacio pensado para cuidar cada detalle de tu instalación.' : 'Todo lo que necesitas para disfrutar de tu próximo partido.' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-link sidebar-logout"><x-workspace-icon name="logout" /><span>Cerrar sesión</span></button>
            </form>
            <div class="sidebar-footer"><span class="status-dot"></span> VAED · Hecho para el deporte</div>
        </div>
    </aside>

    <header class="workspace-topbar">
        <div class="topbar-location">
            <button type="button" class="icon-button mobile-menu-toggle" @click="sidebarOpen = true" :aria-expanded="sidebarOpen.toString()" aria-controls="workspace-sidebar" aria-label="Abrir menú">
                <x-workspace-icon name="menu" />
            </button>
            <span class="topbar-app-icon"><x-workspace-icon name="grid" /></span>
            <span class="topbar-section">{{ $roleLabel }}</span>
            <span class="topbar-divider">/</span>
            <span class="topbar-current">{{ $onDashboard ? 'Vista general' : (request()->routeIs('profile.show') ? 'Mi perfil' : 'Mi cuenta') }}</span>
        </div>
        <div class="topbar-actions">
            <a href="{{ url('/') }}" class="topbar-public">Ir a la web <x-workspace-icon name="external" /></a>
            <span class="topbar-separator"></span>
            <x-dropdown align="right" width="60">
                <x-slot name="trigger">
                    <button type="button" class="account-trigger" @keydown.escape.stop="open = false" aria-label="Abrir menú de cuenta" :aria-expanded="open.toString()" aria-haspopup="true">
                        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                            <img class="account-avatar object-cover" src="{{ auth()->user()->profile_photo_url }}" alt="" />
                        @else
                            <span class="account-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        @endif
                        <span class="account-name"><strong>{{ auth()->user()->name }}</strong><small>{{ $roleLabel }}</small></span>
                        <x-workspace-icon name="chevron" />
                    </button>
                </x-slot>
                <x-slot name="content">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-xs text-slate-500">Sesión iniciada como</p>
                        <p class="mt-1 text-sm font-medium text-slate-800 break-all">{{ auth()->user()->email }}</p>
                    </div>
                    <x-dropdown-link href="{{ route('profile.show') }}">Mi perfil y seguridad</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-start text-sm text-rose-600 hover:bg-rose-50">Cerrar sesión</button>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </header>
</div>
