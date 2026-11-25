@props(['location' => false, 'cities' => [], 'id' => 'market-search'])

@php
    $keyword = request()->query('q');
    $routeCity = request()->route('city');
    $selectedCity = $routeCity instanceof \App\Models\City ? $routeCity->id : request()->query('city');
@endphp
<form class="market-search {{ $location ? 'market-search-nav' : 'market-search-hero' }}" method="GET" action="{{ route('listings.browse') }}" role="search" aria-label="{{ $location ? 'Marketplace search' : 'Homepage search' }}">
    @if ($location)
        <div class="market-search-location">
            <x-market-icon name="pin" />
            <label class="sr-only" for="{{ $id }}-city">Location</label>
            <select id="{{ $id }}-city" name="city">
                <option value="">All locations</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected(is_scalar($selectedCity) && (string) $selectedCity === (string) $city->id)>{{ $city->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <label class="sr-only" for="{{ $id }}">Search listings</label>
    <input id="{{ $id }}" type="search" name="q" maxlength="120" placeholder="Search products and services" value="{{ is_string($keyword) ? $keyword : '' }}">
    <button type="submit" class="market-button" aria-label="Search"><x-market-icon name="search" /></button>
</form>
