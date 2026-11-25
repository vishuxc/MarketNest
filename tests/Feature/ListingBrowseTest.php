<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingBrowseTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seller = User::factory()->create();
    }

    public function test_guest_can_browse_products_and_services_and_follow_homepage_links(): void
    {
        $this->publish('Pocket radio', 'electronics', 'chennai');
        $this->publish('Screen repair', 'services', 'bengaluru', 'service');

        $this->get('/listings')->assertOk()->assertSeeText('2 listings')
            ->assertSeeText('Pocket radio')->assertSeeText('Screen repair')
            ->assertSeeText('Product')->assertSeeText('Service');

        $this->get('/listings?type=service')->assertOk()->assertSeeText('1 listing')
            ->assertSeeText('Screen repair')->assertDontSeeText('Pocket radio');

        $this->get('/')->assertOk()->assertSeeText('Browse all listings')
            ->assertSee(route('listings.category', 'electronics'))
            ->assertSee(route('listings.city', 'chennai'));

        $this->assertGuest();
    }

    public function test_category_route_stays_scoped_and_can_filter_by_subcategory(): void
    {
        $phone = $this->publish('Pocket phone', 'electronics', 'chennai', subcategory: 'mobiles');
        $this->publish('Office notebook', 'electronics', 'bengaluru', subcategory: 'laptops');
        $furniture = $this->publish('Oak sofa', 'furniture', 'chennai');

        $this->get(route('listings.category', ['category' => 'electronics', 'subcategory' => $phone->subcategory_id]))
            ->assertOk()->assertSeeText('1 listing')->assertSeeText('Pocket phone')
            ->assertSeeText('Mobiles')->assertDontSeeText('Office notebook')->assertDontSeeText('Oak sofa');

        $this->get('/category/electronics?category='.$furniture->category_id)
            ->assertOk()->assertSeeText('2 listings')->assertDontSeeText('Oak sofa');
    }

    public function test_city_route_stays_scoped_and_can_filter_by_area(): void
    {
        $phone = $this->publish('Pocket phone', 'electronics', 'chennai', area: 'guindy');
        $this->publish('Oak sofa', 'furniture', 'chennai', area: 'velachery');
        $outside = $this->publish('Southern laptop', 'electronics', 'bengaluru');

        $this->get('/city/chennai?city='.$outside->city_id)->assertOk()
            ->assertSeeText('2 listings')->assertSeeText('Pocket phone')->assertSeeText('Oak sofa')
            ->assertDontSeeText('Southern laptop');

        $this->get('/city/chennai?area='.$phone->area_id)->assertOk()
            ->assertSeeText('1 listing')->assertSeeText('Pocket phone')->assertDontSeeText('Oak sofa');
    }

    public function test_combined_route_requires_both_slug_filters_and_preserves_them(): void
    {
        $match = $this->publish('Pocket phone', 'electronics', 'chennai', subcategory: 'mobiles', area: 'guindy');
        $this->publish('Office laptop', 'electronics', 'chennai', subcategory: 'laptops', area: 'velachery');
        $otherCity = $this->publish('Southern laptop', 'electronics', 'bengaluru');
        $otherCategory = $this->publish('Oak sofa', 'furniture', 'chennai');

        $url = route('listings.city-category', ['city' => 'chennai', 'category' => 'electronics']);
        $this->get($url.'?city='.$otherCity->city_id.'&category='.$otherCategory->category_id)->assertOk()
            ->assertSeeText('Electronics in Chennai')->assertSeeText('2 listings')
            ->assertSeeText('Pocket phone')->assertSeeText('Office laptop')
            ->assertDontSeeText('Southern laptop')->assertDontSeeText('Oak sofa');

        $this->get($url.'?subcategory='.$match->subcategory_id.'&area='.$match->area_id)->assertOk()
            ->assertSeeText('Pocket phone')->assertDontSeeText('Office laptop');
    }

    public function test_selecting_both_city_and_category_redirects_to_combined_slug_route(): void
    {
        $listing = $this->publish('Pocket phone', 'electronics', 'chennai');
        $target = route('listings.city-category', ['city' => 'chennai', 'category' => 'electronics', 'type' => 'product']);

        $this->get('/listings?city='.$listing->city_id.'&category='.$listing->category_id.'&type=product')
            ->assertRedirect($target);
        $this->get('/category/electronics?city='.$listing->city_id.'&type=product')->assertRedirect($target);
        $this->get('/city/chennai?category='.$listing->category_id.'&type=product')->assertRedirect($target);
    }

    public function test_invalid_slugs_and_empty_scopes_are_handled(): void
    {
        foreach (['/category/missing', '/city/missing', '/city/chennai/missing', '/city/missing/electronics'] as $url) {
            $this->get($url)->assertNotFound();
        }

        foreach (['/listings', '/category/electronics', '/city/chennai', '/city/chennai/electronics'] as $url) {
            $this->get($url)->assertOk()->assertSeeText('0 listings')->assertSeeText('No matching listings');
        }
    }

    public function test_filters_validate_existing_records_and_parent_relationships(): void
    {
        $listing = $this->publish('Pocket phone', 'electronics', 'chennai');
        $state = State::whereSlug('kerala')->sole();

        $this->get('/listings?category[]=1&type=unknown')->assertUnprocessable()->assertSeeText('Check your filters');
        $this->get('/category/furniture?subcategory='.$listing->subcategory_id)->assertUnprocessable()
            ->assertSeeText('Choose a subcategory in the selected category.');
        $this->get('/city/bengaluru?area='.$listing->area_id)->assertUnprocessable()
            ->assertSeeText('Choose an area in the selected city.');
        $this->get('/city/chennai?state='.$state->id)->assertUnprocessable()
            ->assertSeeText('Choose a city in the selected location.');
    }

    public function test_country_and_state_filters_use_the_normalized_location(): void
    {
        $this->publish('Pocket phone', 'electronics', 'chennai');
        $this->publish('Southern laptop', 'electronics', 'bengaluru');
        $country = Country::whereCode('IN')->sole();
        $state = State::whereSlug('tamil-nadu')->sole();

        $this->get('/listings?country='.$country->id.'&state='.$state->id)->assertOk()
            ->assertSeeText('Pocket phone')->assertDontSeeText('Southern laptop');
    }

    public function test_pagination_preserves_active_filters(): void
    {
        for ($index = 1; $index <= 13; $index++) {
            $this->publish("Numbered device {$index}", 'electronics', 'chennai');
        }
        $this->publish('Filtered out service', 'electronics', 'chennai', 'service');

        $base = '/city/chennai/electronics?type=product';
        $this->get($base)->assertOk()->assertSeeText('13 listings')
            ->assertSeeText('Numbered device 13')->assertDontSeeText('Filtered out service')
            ->assertSee('type=product&amp;page=2', false);
        $this->get($base.'&page=2')->assertOk()->assertSeeText('13 listings')
            ->assertSeeText('Numbered device 1')->assertDontSeeText('Numbered device 13');
    }

    public function test_listing_details_expose_correct_navigation_without_private_seller_information(): void
    {
        $listing = $this->publish('Pocket phone', 'electronics', 'chennai');

        $this->get(route('listings.show', $listing->slug))->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee(route('listings.category', 'electronics'))
            ->assertSee(route('listings.city', 'chennai'))
            ->assertSee(route('listings.city-category', ['city' => 'chennai', 'category' => 'electronics']))
            ->assertSeeText('Browse Electronics in Chennai')
            ->assertSeeText($this->seller->name)
            ->assertDontSee($this->seller->email);
    }

    public function test_guest_can_load_filter_options(): void
    {
        $category = Category::whereSlug('electronics')->sole();
        $city = City::whereSlug('chennai')->sole();

        $this->getJson(route('listing-options.subcategories', $category))->assertOk()
            ->assertJsonFragment(['name' => 'Mobiles'])->assertJsonMissing(['name' => 'Sofas']);
        $this->getJson(route('listing-options.areas', $city))->assertOk()
            ->assertJsonFragment(['name' => 'Guindy'])->assertJsonMissing(['name' => 'Whitefield']);
    }

    public function test_invalid_browse_filters_return_json_or_preserve_valid_blade_filters(): void
    {
        $base = '/city/chennai/electronics';
        $invalid = $base.'?q=phone&type=product&min_price=100&max_price=50';
        $this->getJson($invalid)->assertUnprocessable()->assertJsonValidationErrors('max_price');
        $this->get($invalid)->assertUnprocessable()->assertSee('value="phone"', false)
            ->assertSee('name="min_price"', false)->assertSee('value="100"', false);

        $this->get($base.'?q=phone&type=product')->assertOk()
            ->assertSee(route('listings.city-category', ['city' => 'chennai', 'category' => 'electronics', 'type' => 'product']))
            ->assertSee('class="market-button market-button-secondary" href="'.route('listings.city-category', ['city' => 'chennai', 'category' => 'electronics']).'"', false)
            ->assertSeeText('Clear filters');
    }

    public function test_keyword_search_includes_listing_and_category_text_and_treats_wildcards_literally(): void
    {
        $listing = $this->publish('Pocket phone', 'electronics', 'chennai', subcategory: 'mobiles');
        $listing->update(['description' => 'Unboxed unit, 90% battery capacity.']);
        $this->publish('Oak sofa', 'furniture', 'chennai');

        foreach (['pocket', 'unboxed', 'electronics', 'mobiles', '90%'] as $keyword) {
            $this->get(route('listings.browse', ['q' => $keyword]))->assertOk()
                ->assertSeeText('1 listing')->assertSeeText('Pocket phone')->assertDontSeeText('Oak sofa')
                ->assertSeeText('Clear search');
        }

        $this->get('/listings?q=%25')->assertOk()->assertSeeText('1 listing');
        $this->get('/listings?q[]=phone')->assertUnprocessable();
        $this->get('/listings?q=unmatched')->assertOk()->assertSeeText('No matching listings');
    }

    public function test_search_combines_with_city_category_type_and_inclusive_price_filters(): void
    {
        $this->publish('Pocket phone', 'electronics', 'chennai')->update(['price' => '700.50']);
        $this->publish('Expensive phone', 'electronics', 'chennai')->update(['price' => '900.00']);
        $this->publish('Southern phone', 'electronics', 'bengaluru')->update(['price' => '700.50']);
        $this->publish('Secondhand phone stand', 'furniture', 'chennai')->update(['price' => '700.50']);
        $this->publish('Phone setup service', 'electronics', 'chennai', 'service')->update(['price' => '700.50']);

        $url = '/city/chennai/electronics?q=phone&type=product&min_price=700.50&max_price=700.50';
        $this->get($url)->assertOk()->assertSeeText('1 listing')->assertSeeText('Pocket phone')
            ->assertDontSeeText('Expensive phone')->assertDontSeeText('Southern phone')
            ->assertDontSeeText('Secondhand phone stand')->assertDontSeeText('Phone setup service');

        $this->get('/listings?min_price=500&max_price=100')->assertUnprocessable()
            ->assertSeeText('Maximum price must be at least the minimum price.');
        $this->get('/listings?min_price=-1')->assertUnprocessable();
        $this->get('/listings?max_price=10.001')->assertUnprocessable();
    }

    public function test_sorting_uses_whitelisted_orders_and_keeps_search_and_prices_in_pagination(): void
    {
        $older = $this->publish('Audio old device', 'electronics', 'chennai');
        $older->forceFill(['price' => '900.00', 'created_at' => now()->subDay()])->save();
        $this->publish('Audio new device', 'electronics', 'chennai')->update(['price' => '100.00']);

        foreach (['oldest', 'price_desc'] as $sort) {
            $this->get('/listings?sort='.$sort)->assertOk()
                ->assertSeeTextInOrder(['Audio old device', 'Audio new device']);
        }
        foreach (['latest', 'price_asc'] as $sort) {
            $this->get('/listings?sort='.$sort)->assertOk()
                ->assertSeeTextInOrder(['Audio new device', 'Audio old device']);
        }
        $this->get('/listings?sort=unsupported')->assertUnprocessable();

        for ($index = 1; $index <= 12; $index++) {
            $this->publish("Audio extra device {$index}", 'electronics', 'chennai');
        }

        $this->get('/listings?q=Audio&min_price=100&max_price=900&sort=price_asc')->assertOk()
            ->assertSeeText('14 listings')->assertSee('q=Audio&amp;sort=price_asc&amp;min_price=100&amp;max_price=900&amp;page=2', false);
    }

    private function publish(
        string $name,
        string $categorySlug,
        string $citySlug,
        string $type = 'product',
        ?string $subcategory = null,
        ?string $area = null,
    ): Listing {
        $category = Category::whereSlug($categorySlug)->sole();
        $city = City::whereSlug($citySlug)->sole();

        return $this->seller->listings()->create([
            'type' => $type,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => 'Marketplace listing.',
            'category_id' => $category->id,
            'subcategory_id' => $category->subcategories()->when($subcategory, fn ($query) => $query->whereSlug($subcategory))->firstOrFail()->id,
            'city_id' => $city->id,
            'area_id' => $city->areas()->when($area, fn ($query) => $query->whereSlug($area))->firstOrFail()->id,
            'price' => '250.00',
        ]);
    }
}
