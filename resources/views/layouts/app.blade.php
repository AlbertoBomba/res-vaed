<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>VAED · {{ auth()->user()->isAdministrator() ? 'Administración' : 'Mi espacio' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased workspace">
        <x-banner />

        <div class="min-h-screen" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" @resize.window="if (window.innerWidth >= 1024) sidebarOpen = false">
            <a href="#main-content" class="workspace-skip-link">Saltar al contenido</a>
            @livewire('navigation-menu')

            <main id="main-content" class="workspace-main" tabindex="-1">
                <div class="workspace-content">
                    @if (isset($header))
                        <div class="workspace-page-heading">{{ $header }}</div>
                    @endif
                    {{ $slot }}
                    <footer class="workspace-page-footer"><span>© {{ date('Y') }} VAED</span><span>Conecta. Reserva. Juega.</span></footer>
                </div>
            </main>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
