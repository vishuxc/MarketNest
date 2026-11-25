<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Services\ListingPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function home(): View
    {
        return view('home', [
            'listings' => Listing::forCards()->limit(12)->get(),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
            'popularCities' => City::select(['id', 'name', 'slug'])->withCount('listings')
                ->orderByDesc('listings_count')->orderBy('name')->limit(6)->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $owned = $request->user()->listings();
        $counts = (clone $owned)->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type');

        return view('listings.index', [
            'listings' => $owned->forCards()->paginate(12),
            'productCount' => (int) ($counts['product'] ?? 0),
            'serviceCount' => (int) ($counts['service'] ?? 0),
        ]);
    }

    public function show(Listing $listing): View
    {
        return view('listings.show', [
            'listing' => $listing->load(['images', 'category', 'subcategory', 'city.state.country', 'area', 'user:id,name']),
            'relatedListings' => Listing::forCards()->where('category_id', $listing->category_id)
                ->whereKeyNot($listing->id)->limit(4)->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Listing);
    }

    public function store(ListingRequest $request, ListingPublisher $publisher): RedirectResponse
    {
        $listing = $publisher->publish($request->user(), $request->listingAttributes(), $request->file('images', []));

        return to_route('listings.show', $listing->slug)->with('status', 'Your listing has been published.');
    }

    public function edit(Listing $listing): View
    {
        Gate::authorize('update', $listing);

        return $this->form($listing->load(['city.state', 'images']));
    }

    public function update(ListingRequest $request, Listing $listing, ListingPublisher $publisher): RedirectResponse
    {
        $saved = $publisher->publish(
            $request->user(), $request->listingAttributes(), $request->file('images', []),
            $listing, $request->validated('remove_images', []),
        );

        return to_route('listings.show', $saved->slug)->with('status', 'Your listing has been updated.');
    }

    public function destroy(Request $request, Listing $listing, ListingPublisher $publisher): RedirectResponse
    {
        Gate::authorize('delete', $listing);
        $publisher->delete($request->user(), $listing);

        return to_route('listings.index')->with('status', 'Your listing has been deleted.');
    }

    private function form(Listing $listing): View
    {
        return view('listings.form', [
            'listing' => $listing,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'countries' => Country::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
