<x-layouts.marketplace :title="$listing->name.' | MarketNest'">
    <div class="listing-section">
        <x-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => $listing->city->name, 'url' => route('listings.city', $listing->city->slug)],
            ['label' => $listing->category->name, 'url' => route('listings.city-category', [$listing->city->slug, $listing->category->slug])],
            ['label' => $listing->name],
        ]" />
        <article class="listing-detail">
            <div class="listing-gallery" aria-label="Listing photos" data-photo-gallery>
                <x-listing-image :image="$listing->images->first()" :alt="$listing->name.' photo 1'" data-primary-photo loading="eager" />
                @if ($listing->images->count() > 1)
                    <div class="listing-thumbnails">
                        @foreach ($listing->images as $image)
                            <a href="{{ $image->publicUrl() }}" data-thumbnail
                                aria-label="Show photo {{ $loop->iteration }}" @if ($loop->first) aria-current="true" @endif>
                                <x-listing-image :image="$image" :alt="$listing->name.' photo '.$loop->iteration" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="listing-detail-body">
                <p class="listing-meta">
                    <span class="listing-type">{{ ucfirst($listing->type) }}</span>
                    <a class="market-nav-link" href="{{ route('listings.category', $listing->category->slug) }}">{{ $listing->category->name }}</a>
                    / {{ $listing->subcategory->name }}
                </p>
                <h1>{{ $listing->name }}</h1>
                <p class="listing-price">{{ $listing->formattedPrice() }}</p>
                <dl class="listing-facts">
                    <div><dt>Location</dt><dd>{{ $listing->area->name }}, <a class="market-nav-link" href="{{ route('listings.city', $listing->city->slug) }}">{{ $listing->city->name }}</a>, {{ $listing->city->state->name }}, {{ $listing->city->state->country->name }}</dd></div>
                    <div><dt>Published</dt><dd><time datetime="{{ $listing->created_at->toDateString() }}">{{ $listing->created_at->format('d M Y') }}</time></dd></div>
                </dl>
                <div class="listing-seller"><x-market-icon name="user" /><div><h2>Seller</h2><p>{{ $listing->user->name }}</p></div></div>
                <h2>Description</h2>
                <p class="listing-description">{{ $listing->description }}</p>
                <p><a class="market-nav-link" href="{{ route('listings.city-category', [$listing->city->slug, $listing->category->slug]) }}">
                    Browse {{ $listing->category->name }} in {{ $listing->city->name }}
                </a></p>
                @can('update', $listing)
                    <div class="listing-actions">
                        <a class="market-button market-button-secondary" href="{{ route('listings.edit', $listing) }}">Edit listing</a>
                        <x-delete-listing :listing="$listing" />
                    </div>
                @endcan
            </div>
        </article>
    </div>
    @if ($relatedListings->isNotEmpty())
        <section class="listing-section" aria-labelledby="related-listings">
            <div class="listing-section-heading"><h2 id="related-listings">Related listings</h2></div>
            <div class="listing-grid">
                @foreach ($relatedListings as $related)
                    <x-listing-card :listing="$related" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.marketplace>
