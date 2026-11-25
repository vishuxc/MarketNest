<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\DemoListingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoListingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_optional_demo_seed_populates_24_illustrated_listings_without_touching_existing_listings(): void
    {
        Storage::fake('public');
        $this->seed();

        $owner = User::factory()->create();
        $category = Category::whereSlug('electronics')->sole();
        $city = City::whereSlug('chennai')->sole();
        $existing = $owner->listings()->create([
            'slug' => 'existing-user-listing', 'type' => 'product', 'name' => 'Existing listing',
            'description' => 'An existing user listing.', 'price' => '999.00',
            'category_id' => $category->id,
            'subcategory_id' => $category->subcategories()->whereSlug('mobiles')->sole()->id,
            'city_id' => $city->id, 'area_id' => $city->areas()->firstOrFail()->id,
        ]);

        $this->seed(DemoListingSeeder::class);
        $demos = Listing::where('slug', 'like', 'demo-marketnest-%')
            ->with(['images', 'user', 'city.state.country', 'area', 'subcategory'])->get();
        $originalImages = ListingImage::orderBy('id')->pluck('image_path')->all();

        $this->assertCount(24, $demos);
        $this->assertCount(18, $demos->where('type', 'product'));
        $this->assertCount(6, $demos->where('type', 'service'));
        $this->assertCount(4, $demos->pluck('user_id')->unique());
        $this->assertCount(4, $demos->filter(fn (Listing $listing) => $listing->images->count() === 2));
        $this->assertCount(28, $originalImages);

        foreach ($demos as $listing) {
            $this->assertStringStartsWith('Demonstration listing.', $listing->description);
            $this->assertStringEndsWith('@example.invalid', $listing->user->email);
            $this->assertSame('IN', $listing->city->state->country->code);
            $this->assertSame($listing->city_id, $listing->area->city_id);
            $this->assertSame($listing->category_id, $listing->subcategory->category_id);
            $this->assertNotEmpty($listing->images);

            foreach ($listing->images as $image) {
                $size = getimagesizefromstring(Storage::disk('public')->get($image->image_path));
                $this->assertSame([800, 500, 'image/webp'], [$size[0], $size[1], $size['mime']]);
            }
        }

        $this->seed(DemoListingSeeder::class);

        $this->assertDatabaseCount('listings', 25);
        $this->assertDatabaseCount('users', 5);
        $this->assertSame($originalImages, ListingImage::orderBy('id')->pluck('image_path')->all());
        $this->assertCount(28, Storage::disk('public')->allFiles('listings'));
        $this->assertSame('999.00', $existing->refresh()->price);

        $phone = $demos->firstWhere('slug', 'demo-marketnest-apple-iphone-13');
        $this->assertSame('25000.00', $phone->price);
        $this->get('/')->assertOk()->assertSeeText('Laptop Repair Service')->assertSeeText('Dumbbell Set');
        $this->get('/category/electronics')->assertOk()->assertSeeText('Dell Inspiron Laptop')->assertDontSeeText('Dumbbell Set');
        $this->get('/city/chennai')->assertOk()->assertSeeText('Apple iPhone 13')->assertDontSeeText('Dell Inspiron Laptop');
        $this->get('/city/chennai/electronics')->assertOk()->assertSeeText('Apple iPhone 13')->assertDontSeeText('Three-seater Sofa');
        $this->get('/listings?q=laptop&sort=price_asc')->assertOk()
            ->assertSeeTextInOrder(['Laptop Repair Service', 'Dell Inspiron Laptop']);
        $this->get(route('listings.show', $phone->slug))->assertOk()->assertSee('data-thumbnail', false)
            ->assertSee($phone->images->first()->image_path);
    }
}
