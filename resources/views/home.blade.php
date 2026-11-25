<x-layouts.marketplace title="MarketNest | Local Marketplace">
    <section class="market-container market-hero" aria-labelledby="home-heading">
        <div class="market-hero-copy">
            <h1 id="home-heading">Great finds.<br><span>Closer to home.</span></h1>
            <p>Find secondhand favourites and local services, or list something for buyers in your neighbourhood.</p>
            <x-market-search id="hero-search" />
            <div class="listing-actions">
                <a class="market-button" href="{{ route('listings.browse') }}">Explore Marketplace</a>
                <a class="market-button market-button-secondary" href="{{ route('listings.create') }}"><x-market-icon name="plus" /> Sell an Item</a>
            </div>
        </div>
        <div class="market-hero-art" aria-hidden="true">
            <i></i><i></i><i></i><x-market-icon name="mark" /><span></span>
        </div>
    </section>

    @if ($categories->isNotEmpty())
        <section class="listing-section" aria-labelledby="browse-categories">
            <div class="listing-section-heading"><h2 id="browse-categories">Browse by category</h2></div>
            <ul class="browse-category-grid">
                @foreach ($categories as $category)
                    <li><a href="{{ route('listings.category', $category->slug) }}"><span><x-market-icon :name="$category->slug" /></span>{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="listing-section listing-recent" aria-labelledby="recent-listings-heading">
        <div class="listing-section-heading">
            <h2 id="recent-listings-heading">Recent listings</h2>
            <a class="market-nav-link" href="{{ route('listings.browse') }}">Browse all listings</a>
        </div>
        @if ($listings->isEmpty())
            <x-listing-empty heading="No listings yet" message="Be the first to share a product or service with your community." />
        @else
            <div class="listing-grid">
                @foreach ($listings as $listing)
                    <x-listing-card :listing="$listing" />
                @endforeach
            </div>
        @endif
    </section>

    @if ($popularCities->isNotEmpty())
        <section class="listing-section" aria-labelledby="popular-cities">
            <div class="listing-section-heading"><h2 id="popular-cities">Popular cities</h2></div>
            <ul class="browse-cities">
                @foreach ($popularCities as $city)
                    <li><a href="{{ route('listings.city', $city->slug) }}"><x-market-icon name="pin" />{{ $city->name }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.marketplace>
