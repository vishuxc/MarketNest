@props(['listing', 'manage' => false])

<article class="listing-card">
    <a class="listing-card-media" href="{{ route('listings.show', $listing->slug) }}" tabindex="-1" aria-hidden="true">
        <x-listing-image :image="$listing->primaryImage" />
        <span class="listing-type">{{ ucfirst($listing->type) }}</span>
    </a>
    <div class="listing-card-body">
        <p class="listing-price">{{ $listing->formattedPrice() }}</p>
        <h3><a href="{{ route('listings.show', $listing->slug) }}">{{ $listing->name }}</a></h3>
        <p class="listing-meta">{{ $listing->category->name }} <span class="sr-only">{{ ucfirst($listing->type) }}</span></p>
        <div class="listing-card-meta">
            <p class="listing-meta"><x-market-icon name="pin" />{{ $listing->area->name }}, {{ $listing->city->name }}</p>
            <time class="listing-meta" datetime="{{ $listing->created_at->toDateString() }}">{{ $listing->created_at->format('d M Y') }}</time>
        </div>
        @if ($manage)
            <div class="listing-actions">
                <a class="market-button market-button-secondary" href="{{ route('listings.show', $listing->slug) }}">View</a>
                <a class="market-button market-button-secondary" href="{{ route('listings.edit', $listing) }}">Edit</a>
                <x-delete-listing :listing="$listing" />
            </div>
        @endif
    </div>
</article>
