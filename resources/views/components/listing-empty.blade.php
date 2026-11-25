@props(['heading', 'message'])

<div class="listing-empty">
    <h2>{{ $heading }}</h2>
    <p>{{ $message }}</p>
    <a class="market-button" href="{{ route('listings.create') }}">Create listing</a>
</div>
