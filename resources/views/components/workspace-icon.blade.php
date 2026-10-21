@props(['name'])

<svg {{ $attributes->merge(['class' => 'workspace-icon']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('grid')
            <rect x="3" y="3" width="7" height="7" rx="2" /><rect x="14" y="3" width="7" height="7" rx="2" /><rect x="3" y="14" width="7" height="7" rx="2" /><rect x="14" y="14" width="7" height="7" rx="2" />
            @break
        @case('court')
            <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M12 4v16M3 12h18M6 4v16M18 4v16" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="3" /><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('wallet')
            <path d="M20 8V5a2 2 0 0 0-2-2L5 6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h15V8H5M16 12h5v5h-5a2.5 2.5 0 0 1 0-5Z" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="4" /><path d="M4 21v-2a8 8 0 0 1 16 0v2" />
            @break
        @case('shield')
            <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z" /><path d="m9 12 2 2 4-4" />
            @break
        @case('arrow')
            <path d="M5 12h14m-5-5 5 5-5 5" />
            @break
        @case('external')
            <path d="M14 3h7v7M21 3 10 14M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5" />
            @break
        @case('logout')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M10 12h11m-4-4 4 4-4 4" />
            @break
        @case('chevron')
            <path d="m8 10 4 4 4-4" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('close')
            <path d="m6 6 12 12M6 18 18 6" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('sparkles')
            <path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3ZM20 2v4M18 4h4" />
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="3" /><path d="m3 6 9 7 9-7" />
            @break
        @case('ball')
            <circle cx="12" cy="12" r="9" /><path d="M6 5a9 9 0 0 1 0 14M18 5a9 9 0 0 0 0 14" />
            @break
        @case('key')
            <circle cx="8" cy="8" r="5" /><path d="m12 12 9 9m-4-4 3-3m-6 0 3-3" />
            @break
    @endswitch
</svg>
