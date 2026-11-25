<x-layouts.marketplace :title="$heading.' | MarketNest'">
    <section class="listing-section" aria-labelledby="browse-heading">
        <x-breadcrumbs :items="$breadcrumbs" />
        <div class="listing-section-heading">
            <h1 id="browse-heading">{{ $heading }}</h1>
            @if ($filterErrors->isEmpty())
                <p class="listing-meta">{{ number_format($listings->total()) }} {{ Str::plural('listing', $listings->total()) }}</p>
            @endif
        </div>

        @if (isset($filters['q']))
            <p class="browse-search-summary">Results for <strong>“{{ $filters['q'] }}”</strong>
                <a class="market-nav-link" href="{{ $clearSearchUrl }}">Clear search</a>
            </p>
        @endif
        <div class="browse-layout">
            <aside aria-label="Listing filters">
                <details class="browse-filters" open data-browse-filters @if ($filterErrors->isNotEmpty()) data-has-errors @endif>
                    <summary>Filters</summary>
                    <form method="GET" action="{{ $clearUrl }}" data-browse-form>
                        @if ($filterErrors->has('page') || ($options['city'] === null && $filterErrors->has('city')))
                            <ul class="auth-error">
                                @foreach ($filterErrors->getMessages() as $field => $messages)
                                    @if ($field === 'page' || ($field === 'city' && $options['city'] === null))
                                        @foreach ($messages as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                        <div class="listing-field">
                            <label for="browse-keyword">Keyword</label>
                            <input id="browse-keyword" type="search" name="q" maxlength="120" value="{{ $filters['q'] ?? '' }}"
                                placeholder="Search listings" @if ($filterErrors->has('q')) aria-invalid="true" aria-describedby="browse-keyword-error" @endif>
                            @if ($filterErrors->has('q')) <p class="auth-error" id="browse-keyword-error">{{ $filterErrors->first('q') }}</p> @endif
                        </div>
                        @if ($options['category'] !== null)
                            <x-browse-select name="category" label="Category" :choices="$options['category']->pluck('name', 'id')"
                                :selected="$filters['category'] ?? null" :error="$filterErrors->first('category')" />
                        @endif
                        <x-browse-select name="subcategory" label="Subcategory" :choices="$options['subcategory']?->pluck('name', 'id') ?? []"
                            :selected="$filters['subcategory'] ?? null" :error="$filterErrors->first('subcategory')"
                            :disabled="$options['subcategory'] === null"
                            data-parent="category" :data-options="route('listing-options.subcategories', '__parent__')" />
                        <x-browse-select name="country" label="Country" :choices="$options['country']"
                            :selected="$filters['country'] ?? null" :error="$filterErrors->first('country')" />
                        <x-browse-select name="state" label="State" :choices="$options['state']->pluck('name', 'id')"
                            :metadata="$options['state']->mapWithKeys(fn ($state) => [$state->id => ['country' => $state->country_id]])"
                            :selected="$filters['state'] ?? null" :error="$filterErrors->first('state')" />
                        @if ($options['city'] !== null)
                            <x-browse-select name="city" label="City" :choices="$options['city']->pluck('name', 'id')"
                                :metadata="$options['city']->mapWithKeys(fn ($city) => [$city->id => ['state' => $city->state_id, 'country' => $city->country_id]])"
                                :selected="$filters['city'] ?? null" :error="$filterErrors->first('city')" />
                        @endif
                        <x-browse-select name="area" label="Area" :choices="$options['area']?->pluck('name', 'id') ?? []"
                            :selected="$filters['area'] ?? null" :error="$filterErrors->first('area')"
                            :disabled="$options['area'] === null"
                            data-parent="city" :data-options="route('listing-options.areas', '__parent__')" />
                        <x-browse-select name="type" label="Listing type" :choices="['product' => 'Product', 'service' => 'Service']"
                            :selected="$filters['type'] ?? null" :error="$filterErrors->first('type')" />
                        <fieldset class="browse-price-range">
                            <legend>Price range (₹)</legend>
                            <div class="browse-price-fields">
                                @foreach (['min_price' => ['Min price', '0'], 'max_price' => ['Max price', 'Any']] as $name => [$label, $placeholder])
                                    <div class="listing-field">
                                        <label for="browse-{{ $name }}">{{ $label }}</label>
                                        <input id="browse-{{ $name }}" name="{{ $name }}" type="text" inputmode="decimal" maxlength="13"
                                            value="{{ $filters[$name] ?? '' }}" placeholder="{{ $placeholder }}"
                                            @if ($filterErrors->has($name)) aria-invalid="true" aria-describedby="browse-{{ $name }}-error" @endif>
                                        @if ($filterErrors->has($name)) <p class="auth-error" id="browse-{{ $name }}-error">{{ $filterErrors->first($name) }}</p> @endif
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                        <x-browse-select name="sort" label="Sort by" :choices="['oldest' => 'Oldest', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low']"
                            placeholder="Latest listings" :selected="$filters['sort'] ?? null" :error="$filterErrors->first('sort')" />
                        <div class="browse-filter-actions">
                            <button class="market-button" type="submit">Apply filters</button>
                            <a class="market-button market-button-secondary" href="{{ $clearUrl }}">
                                <x-market-icon name="reset" /> Clear filters
                            </a>
                        </div>
                    </form>
                </details>
            </aside>

            <div class="browse-results">
                @if ($activeFilters)
                    <ul class="browse-tags" aria-label="Active filters">
                        @foreach ($activeFilters as $filter)
                            <li>{{ $filter }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($filterErrors->isNotEmpty())
                    <div class="listing-empty"><h2>Check your filters</h2><p>Correct the highlighted values and apply the filters again.</p></div>
                @elseif ($listings->isEmpty())
                    <div class="listing-empty">
                        <h2>{{ $listings->total() ? 'No listings on this page' : 'No matching listings' }}</h2>
                        <p>{{ $listings->total() ? 'Return to the first page to see the current results.' : 'Try changing the filters or browse the full marketplace.' }}</p>
                        @if ($listings->total())
                            <a class="market-button market-button-secondary" href="{{ $listings->url(1) }}">First page</a>
                        @else
                            <a class="market-button market-button-secondary" href="{{ route('listings.browse') }}">Browse all listings</a>
                        @endif
                    </div>
                @else
                    <div class="listing-grid">
                        @foreach ($listings as $listing)
                            <x-listing-card :listing="$listing" />
                        @endforeach
                    </div>
                    <div class="listing-pagination">{{ $listings->links() }}</div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.marketplace>
