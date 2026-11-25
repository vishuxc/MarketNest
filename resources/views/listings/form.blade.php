@php
    $editing = $listing->exists;
    $heading = $editing ? 'Edit listing' : 'Publish a listing';
    $value = static function (string $field, mixed $default = ''): string {
        $value = old($field, $default);

        return is_scalar($value) ? (string) $value : '';
    };
    $errorsFor = static fn (string $field) => $errors->has($field)
        ? new \Illuminate\View\ComponentAttributeBag(['aria-invalid' => 'true', 'aria-describedby' => $field.'-error'])
        : new \Illuminate\View\ComponentAttributeBag;
    $children = [
        ['subcategory_id', 'Subcategory', 'category_id', 'subcategories', $listing->subcategory_id],
        ['state_id', 'State', 'country_id', 'states', $listing->city?->state_id],
        ['city_id', 'City', 'state_id', 'cities', $listing->city_id],
        ['area_id', 'Area', 'city_id', 'areas', $listing->area_id],
    ];
    $removed = old('remove_images', []);
    $removed = is_array($removed) ? $removed : [];
@endphp

<x-layouts.marketplace :title="$heading.' | MarketNest'">
    <section class="listing-section listing-form-section" aria-labelledby="listing-form-heading">
        <h1 id="listing-form-heading">{{ $heading }}</h1>
        <form class="listing-form" method="POST" enctype="multipart/form-data" data-listing-form
            action="{{ $editing ? route('listings.update', $listing) : route('listings.store') }}">
            @csrf
            @if ($editing)
                @method('PATCH')
            @endif

            <fieldset class="listing-form-group">
                <legend>Listing type</legend>
                <div class="listing-type-options" @if ($errors->has('type')) aria-describedby="type-error" @endif>
                    @foreach (['product' => ['Product', 'Sell a physical item', 'electronics'], 'service' => ['Service', 'Offer your time or skills', 'services']] as $type => [$label, $help, $icon])
                        <label class="listing-type-option">
                            <input type="radio" name="type" value="{{ $type }}" required @checked($value('type', $listing->type) === $type)>
                            <x-market-icon :name="$icon" />
                            <span><strong>{{ $label }}</strong><small>{{ $help }}</small></span>
                        </label>
                    @endforeach
                </div>
                @error('type') <p class="auth-error" id="type-error">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset class="listing-form-group">
                <legend>Basic information</legend>
                <div class="listing-form-grid">
                    <x-listing-field name="name" label="Name" class="listing-field-wide">
                        <input id="name" name="name" required maxlength="150" placeholder="e.g. Solid oak writing desk" value="{{ $value('name', $listing->name) }}" {{ $errorsFor('name') }}>
                    </x-listing-field>
                    <x-listing-field name="description" label="Description" class="listing-field-wide">
                        <textarea id="description" name="description" rows="5" maxlength="5000" required placeholder="Describe the condition, features, or what your service includes." {{ $errorsFor('description') }}>{{ $value('description', $listing->description) }}</textarea>
                    </x-listing-field>
                </div>
            </fieldset>

            <fieldset class="listing-form-group">
                <legend>Category and subcategory</legend>
                <div class="listing-form-grid">
                    <x-listing-field name="category_id" label="Category">
                        <select id="category_id" name="category_id" required {{ $errorsFor('category_id') }}>
                            <option value="">Choose a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($value('category_id', $listing->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </x-listing-field>
                    <x-listing-dependent :field="$children[0]" :value="$value" :errors-for="$errorsFor" />
                </div>
            </fieldset>

            <fieldset class="listing-form-group">
                <legend>Location</legend>
                <div class="listing-form-grid">
                    <x-listing-field name="country_id" label="Country">
                        <select id="country_id" name="country_id" required {{ $errorsFor('country_id') }}>
                            <option value="">Choose a country</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected($value('country_id', $listing->city?->state?->country_id) === (string) $country->id)>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </x-listing-field>
                    @foreach (array_slice($children, 1) as $field)
                        <x-listing-dependent :field="$field" :value="$value" :errors-for="$errorsFor" />
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="listing-form-group">
                <legend>Pricing</legend>
                <div class="listing-form-grid">
                    <x-listing-field name="price" label="Price (INR)">
                        <input id="price" name="price" type="text" inputmode="decimal" maxlength="13" required
                            placeholder="e.g. 2500.00" value="{{ $value('price', $listing->price) }}" {{ $errorsFor('price') }}>
                    </x-listing-field>
                </div>
            </fieldset>

            <fieldset class="listing-form-group">
                <legend>Listing photos</legend>
                @if ($editing && $listing->images->isNotEmpty())
                    <div class="listing-previews" data-existing-photos>
                        @foreach ($listing->images as $image)
                            <label class="listing-preview">
                                <x-listing-image :image="$image" :alt="'Current photo '.$loop->iteration" />
                                <span><input type="checkbox" name="remove_images[]" value="{{ $image->id }}"
                                    @checked(in_array($image->id, $removed))> Remove photo {{ $loop->iteration }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <x-listing-field name="images" label="Add photos" class="listing-upload">
                    <input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" multiple
                        aria-describedby="listing-image-help{{ $errors->has('images') ? ' images-error' : '' }}">
                    <p id="listing-image-help" class="listing-meta">Up to 5 photos total. JPG, JPEG, PNG or WebP, up to 5 MB each.</p>
                    <p class="auth-error" role="status" data-image-status></p>
                    <div class="listing-previews" data-new-photos></div>
                </x-listing-field>
                @foreach ($errors->get('images.*') + $errors->get('remove_images*') as $messages)
                    @foreach ($messages as $message)
                        <p class="auth-error">{{ $message }}</p>
                    @endforeach
                @endforeach
            </fieldset>

            <noscript><p class="auth-error">Enable JavaScript to select a subcategory and location.</p></noscript>
            <div class="listing-actions">
                <button class="market-button" type="submit" data-listing-submit disabled>{{ $editing ? 'Save changes' : 'Publish listing' }}</button>
                <a class="market-nav-link" href="{{ $editing ? route('listings.show', $listing->slug) : route('home') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.marketplace>
