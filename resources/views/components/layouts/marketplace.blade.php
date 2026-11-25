@props(['title' => config('app.name')])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MarketNest is a local marketplace for products and services.">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="market-body">
    <a href="#main-content" class="market-skip">Skip to content</a>
    <header class="market-header">
        <nav aria-label="Main navigation" class="market-container market-header-inner">
            <x-brand />
            <x-market-search location :cities="$navigationCities" />
            <div class="market-header-actions">
                <a class="market-nav-link" href="{{ route('listings.browse') }}">Browse</a>
                @auth
                    <details class="market-profile">
                        <summary><x-market-icon name="user" /><span>{{ auth()->user()->name }}</span><x-market-icon name="chevron" /></summary>
                        <div class="market-profile-menu">
                            <a href="{{ route('listings.index') }}">My Listings</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Logout</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a class="market-nav-link" href="{{ route('login') }}">Login</a>
                    <a class="market-nav-link" href="{{ route('register') }}">Register</a>
                @endauth
                <a class="market-button" href="{{ route('listings.create') }}"><x-market-icon name="plus" /> Sell</a>
            </div>
        </nav>
    </header>

    <main id="main-content" tabindex="-1">
        @if (session('status'))
            <div class="market-container market-notice">
                <p class="market-status" role="status">{{ session('status') }}</p>
            </div>
        @endif
        {{ $slot }}
    </main>

    <footer class="market-footer">
        <div class="market-container market-footer-inner">
            <div><x-brand /><p>Discover local. Buy smart. Sell easy.</p></div>
            <nav aria-label="Footer navigation">
                <a href="{{ route('listings.browse') }}">Browse marketplace</a>
                <a href="{{ route('listings.create') }}">Sell an item</a>
            </nav>
            <p>&copy; {{ now()->year }} MarketNest</p>
        </div>
    </footer>
</body>
</html>
