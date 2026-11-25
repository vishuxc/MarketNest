<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Models\State;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

class ListingBrowseController extends Controller
{
    public function index(Request $request): Response|RedirectResponse|JsonResponse
    {
        return $this->browse($request);
    }

    public function category(Request $request, Category $category): Response|RedirectResponse|JsonResponse
    {
        return $this->browse($request, category: $category);
    }

    public function city(Request $request, City $city): Response|RedirectResponse|JsonResponse
    {
        return $this->browse($request, city: $city);
    }

    public function combined(Request $request, City $city, Category $category): Response|RedirectResponse|JsonResponse
    {
        return $this->browse($request, $city, $category);
    }

    private function browse(Request $request, ?City $city = null, ?Category $category = null): Response|RedirectResponse|JsonResponse
    {
        $input = $request->query();

        if ($city) {
            $input['city'] = $city->id;
        }

        if ($category) {
            $input['category'] = $category->id;
        }

        $tables = [
            'category' => 'categories', 'subcategory' => 'subcategories', 'country' => 'countries',
            'state' => 'states', 'city' => 'cities', 'area' => 'areas',
        ];
        $rules = ['type' => ['nullable', Rule::in(['product', 'service'])]];
        $rules['page'] = ['nullable', 'integer', 'min:1', 'max:1000000'];
        $rules['q'] = ['nullable', 'string', 'max:120'];
        $rules['sort'] = ['nullable', Rule::in(['latest', 'oldest', 'price_asc', 'price_desc'])];
        foreach (['min_price', 'max_price'] as $field) {
            $rules[$field] = ['nullable', 'numeric', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/'];
        }

        foreach ($tables as $field => $table) {
            $rules[$field] = ['bail', 'nullable', 'integer', 'min:1', Rule::exists($table, 'id')];
        }

        $validator = Validator::make($input, $rules);
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                $this->validateRelationships($validator->safe()->all(), $validator->errors());
            }
        });
        $valid = $validator->passes();
        if (! $valid && $request->expectsJson()) {
            return response()->json([
                'message' => 'Check the highlighted filters.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $values = $valid ? $validator->validated() : Arr::only($validator->valid(), array_keys($rules));
        $filters = array_filter($values, static fn ($value) => $value !== null);
        $selectedCity = $city ?? (isset($filters['city']) ? City::find($filters['city']) : null);
        $selectedCategory = $category ?? (isset($filters['category']) ? Category::find($filters['category']) : null);

        if ($valid && $selectedCity && $selectedCategory && (! $city || ! $category)) {
            return to_route('listings.city-category', [
                'city' => $selectedCity->slug,
                'category' => $selectedCategory->slug,
                ...Arr::except($filters, ['city', 'category']),
            ]);
        }

        $clearUrl = match (true) {
            $city !== null && $category !== null => route('listings.city-category', [$city->slug, $category->slug]),
            $city !== null => route('listings.city', $city->slug),
            $category !== null => route('listings.category', $category->slug),
            default => route('listings.browse'),
        };
        $heading = match (true) {
            $city !== null && $category !== null => "{$category->name} in {$city->name}",
            $city !== null => $city->name,
            $category !== null => $category->name,
            default => 'Browse listings',
        };

        $queryParameters = Arr::except($filters, [
            'page', ...($city ? ['city'] : []), ...($category ? ['category'] : []),
        ]);
        $withoutSearch = Arr::except($queryParameters, ['q']);
        $listings = $valid
            ? Listing::forCards()->filter($filters)->sorted($filters['sort'] ?? 'latest')
                ->paginate(12, page: (int) ($filters['page'] ?? 1))->appends($queryParameters)
            : new LengthAwarePaginator([], 0, 12);

        $breadcrumbs = [['label' => 'Home', 'url' => route('home')]];
        if ($city && $category) {
            $breadcrumbs[] = ['label' => $city->name, 'url' => route('listings.city', $city->slug)];
        }
        $breadcrumbs[] = ['label' => $category?->name ?? $heading];

        $options = [
            'category' => $category ? null : Category::query()
                ->when($city, fn ($query) => $query->whereRelation('listings', 'city_id', $city->id))
                ->orderBy('name')->get(['id', 'name']),
            'country' => Country::orderBy('name')->pluck('name', 'id'),
            'state' => State::orderBy('name')->get(['id', 'name', 'country_id']),
            'city' => $city ? null : City::query()->join('states', 'states.id', '=', 'cities.state_id')
                ->orderBy('cities.name')->get(['cities.id', 'cities.name', 'cities.state_id', 'states.country_id']),
            'subcategory' => $selectedCategory?->subcategories()->orderBy('name')->get(['id', 'name']),
            'area' => $selectedCity?->areas()->orderBy('name')->get(['id', 'name']),
        ];
        $activeFilters = [
            $selectedCategory?->name,
            $options['subcategory']?->firstWhere('id', $filters['subcategory'] ?? null)?->name,
            isset($filters['country']) ? $options['country']->get($filters['country']) : null,
            $options['state']->firstWhere('id', $filters['state'] ?? null)?->name,
            $selectedCity?->name,
            $options['area']?->firstWhere('id', $filters['area'] ?? null)?->name,
            isset($filters['type']) ? ucfirst($filters['type']) : null,
            isset($filters['min_price']) ? 'Min ₹'.$filters['min_price'] : null,
            isset($filters['max_price']) ? 'Max ₹'.$filters['max_price'] : null,
        ];

        return response()->view('listings.browse', [
            'listings' => $listings,
            'heading' => $heading,
            'breadcrumbs' => $breadcrumbs,
            'filters' => $filters,
            'options' => $options,
            'activeFilters' => array_filter($activeFilters),
            'filterErrors' => $validator->errors(),
            'clearUrl' => $clearUrl,
            'clearSearchUrl' => $clearUrl.($withoutSearch ? '?'.Arr::query($withoutSearch) : ''),
        ], $valid ? 200 : 422);
    }

    private function validateRelationships(array $filters, MessageBag $errors): void
    {
        if (isset($filters['min_price'], $filters['max_price'])
            && (float) $filters['min_price'] > (float) $filters['max_price']) {
            $errors->add('max_price', 'Maximum price must be at least the minimum price.');
        }

        $country = $filters['country'] ?? null;
        $state = isset($filters['state']) ? State::find($filters['state']) : null;
        $city = $filters['city'] ?? null;

        if ($country && $state && $state->country_id !== (int) $country) {
            $errors->add('state', 'Choose a state in the selected country.');
        }

        if ($city && ($state || $country) && ! City::whereKey($city)
            ->when($state, fn ($query) => $query->where('state_id', $state->id))
            ->when($country, fn ($query) => $query->whereRelation('state', 'country_id', $country))
            ->exists()) {
            $errors->add('city', 'Choose a city in the selected location.');
        }

        if (isset($filters['subcategory']) && (! isset($filters['category'])
            || ! Subcategory::whereKey($filters['subcategory'])->where('category_id', $filters['category'])->exists())) {
            $errors->add('subcategory', 'Choose a subcategory in the selected category.');
        }

        if (isset($filters['area']) && (! $city
            || ! Area::whereKey($filters['area'])->where('city_id', $city)->exists())) {
            $errors->add('area', 'Choose an area in the selected city.');
        }
    }
}
