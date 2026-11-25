<x-layouts.marketplace title="My Listings | MarketNest">
    <section class="listing-section" aria-labelledby="my-listings-heading">
        <div class="listing-section-heading">
            <h1 id="my-listings-heading">My Listings</h1>
            <a class="market-button" href="{{ route('listings.create') }}"><x-market-icon name="plus" /> Create New Listing</a>
        </div>
        <dl class="listing-stats">
            <div><dt>Total listings</dt><dd>{{ $listings->total() }}</dd></div>
            <div><dt>Products</dt><dd>{{ $productCount }}</dd></div>
            <div><dt>Services</dt><dd>{{ $serviceCount }}</dd></div>
        </dl>
        @if ($listings->isEmpty())
            <x-listing-empty heading="You have no listings on this page" message="Publish a product or service to reach buyers in your area." />
            @if ($listings->currentPage() > 1)
                <p class="listing-pagination"><a class="market-nav-link" href="{{ route('listings.index') }}">Back to your listings</a></p>
            @endif
        @else
            <div class="listing-grid">
                @foreach ($listings as $listing)
                    <x-listing-card :listing="$listing" manage />
                @endforeach
            </div>
            <div class="listing-pagination">{{ $listings->links() }}</div>
        @endif
    </section>
</x-layouts.marketplace>
