@props(['title' => 'Reserva tu próxima pista'])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Encuentra tu pista, consulta horarios disponibles y reserva tu próximo partido con VAED.">
    <title>{{ $title }} · VAED</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-booking">
    <a href="#main-content" class="sr-only focus:not-sr-only">Saltar al contenido</a>
    <header class="public-header">
        <a href="{{ route('courts.index') }}" class="public-brand" aria-label="VAED - Inicio">
            <x-workspace-icon name="court" /><span>vaed<span class="text-violet-400">.</span><small>sport & community</small></span>
        </a>
        <nav aria-label="Navegación principal" class="public-navigation">
            <a href="{{ route('courts.index') }}" @if(request()->routeIs('courts.index')) aria-current="page" @endif>Reservar pista</a>
            @auth
                <a href="{{ route('bookings.index') }}" @if(request()->routeIs('bookings.index')) aria-current="page" @endif>Mis reservas</a>
                <a href="{{ route('dashboard') }}" class="public-account">{{ auth()->user()->isAdministrator() ? 'Administración' : 'Mi cuenta' }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Salir</button></form>
            @else
                <a href="{{ route('login') }}">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="workspace-button court-primary">Crear cuenta</a>
            @endauth
        </nav>
    </header>
    <main id="main-content" class="public-main">
        @if(session('status'))
            <div role="status" class="court-alert alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="court-alert alert-error">
                <div><strong>Revisa los datos antes de continuar.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif
        {{ $slot }}
    </main>
    <footer class="public-footer"><span>© {{ now()->year }} VAED</span><span>Conecta. Reserva. Juega.</span></footer>
</body>
</html>
