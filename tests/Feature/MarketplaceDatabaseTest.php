<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MarketplaceDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeatable_seeders_create_the_category_and_location_hierarchies(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('categories', 7);
        $this->assertDatabaseCount('subcategories', 22);
        $this->assertDatabaseCount('countries', 1);
        $this->assertDatabaseCount('states', 4);
        $this->assertDatabaseCount('cities', 6);
        $this->assertDatabaseCount('areas', 15);
        $this->assertDatabaseEmpty('listings');
        $this->assertDatabaseEmpty('users');

        $electronics = Category::whereSlug('electronics')->sole();
        $accessories = $electronics->subcategories()->whereSlug('accessories')->sole();
        $this->assertTrue($accessories->category->is($electronics));
        $this->assertSame('fashion', Category::whereSlug('fashion')->sole()
            ->subcategories()->whereSlug('accessories')->sole()->category->slug);

        $india = Country::whereCode('IN')->sole();
        $tamilNadu = $india->states()->whereSlug('tamil-nadu')->sole();
        $chennai = $tamilNadu->cities()->whereSlug('chennai')->sole();
        $guindy = $chennai->areas()->whereSlug('guindy')->sole();
        $this->assertTrue($guindy->city->state->country->is($india));
    }

    public function test_listings_use_decimal_prices_and_deleting_a_listing_removes_its_image_records(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $listing = $user->listings()->create($this->listingAttributes());
        $image = $listing->images()->create(['image_path' => 'listings/desk.jpg']);

        $this->assertSame('1200.50', $listing->refresh()->price);
        $this->assertTrue($listing->user->is($user));
        $this->assertSame('tables', $listing->subcategory->slug);
        $this->assertSame('guindy', $listing->area->slug);
        $this->assertTrue($image->listing->is($listing));

        $listing->delete();

        $this->assertModelMissing($image);
    }

    #[DataProvider('mismatchedParents')]
    public function test_mismatched_listing_parents_are_rejected(string $field, string $slug): void
    {
        $this->seed();
        $user = User::factory()->create();
        $attributes = $this->listingAttributes();
        $attributes[$field] = match ($field) {
            'category_id' => Category::whereSlug($slug)->sole()->id,
            'city_id' => City::whereSlug($slug)->sole()->id,
        };

        $this->expectException(QueryException::class);

        $user->listings()->create($attributes);
    }

    public static function mismatchedParents(): array
    {
        return [
            'subcategory from another category' => ['category_id', 'electronics'],
            'area from another city' => ['city_id', 'kochi'],
        ];
    }

    private function listingAttributes(): array
    {
        $category = Category::whereSlug('furniture')->sole();
        $city = City::whereSlug('chennai')->sole();

        return [
            'type' => 'product',
            'name' => 'Wooden desk',
            'slug' => 'wooden-desk',
            'description' => 'Solid wood desk in good condition.',
            'category_id' => $category->id,
            'subcategory_id' => $category->subcategories()->whereSlug('tables')->sole()->id,
            'city_id' => $city->id,
            'area_id' => $city->areas()->whereSlug('guindy')->sole()->id,
            'price' => '1200.50',
        ];
    }
}
