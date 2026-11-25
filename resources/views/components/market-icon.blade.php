@props(['name'])
<svg {{ $attributes->class('market-icon') }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('mark') <path d="m3 11 9-7 9 7M5 10v10h14V10M9 20v-6h6v6"/> @break
        @case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/> @break
        @case('pin') <path d="M19 10c0 5-7 10-7 10S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('user') <circle cx="12" cy="8" r="3.5"/><path d="M5 20v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/> @break
        @case('chevron') <path d="m6 9 6 6 6-6"/> @break
        @case('reset') <path d="M3 12a9 9 0 1 1 2.64 6.36M3 6v6h6"/> @break
        @case('electronics') <rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 17h2"/> @break
        @case('vehicles') <path d="m5 11 2-5h10l2 5m-14 0h14l1 2v5H4v-5l1-2ZM7 15h2m6 0h2M6 18v2m12-2v2"/> @break
        @case('furniture') <path d="M5 13V9a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4M5 13H3v5h18v-5h-2M6 18v2m12-2v2m-9-6h6"/> @break
        @case('fashion') <path d="m8 5-5 3 2 5 3-1v7h8v-7l3 1 2-5-5-3a4 4 0 0 1-8 0Z"/> @break
        @case('home-appliances') <rect x="5" y="3" width="14" height="18" rx="2"/><circle cx="12" cy="13" r="4"/><path d="M8 6h1m3 0h4"/> @break
        @case('services') <path d="m14 7 3 3 4-4a6 6 0 0 1-8 8l-6 6-3-3 6-6a6 6 0 0 1 8-8l-4 4Z"/> @break
        @case('sports') <path d="M3 10v4m4-7v10m10-10v10m4-7v4M7 12h10"/> @break
        @case('photo') <rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.5"/><path d="m4 17 5-4 3 3 4-5 4 5"/> @break
        @default <path d="M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm10 0h6v6h-6z"/>
    @endswitch
</svg>
