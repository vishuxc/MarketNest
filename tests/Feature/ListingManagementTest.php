<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Models\State;
use App\Models\User;
use App\Services\ListingPublisher;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');
    }

    public function test_guests_cannot_create_listings_or_access_the_owner_dashboard(): void
    {
        $this->get(route('listings.create'))->assertRedirectToRoute('login');
        $this->post(route('listings.store'))->assertRedirectToRoute('login');
        $this->get(route('listings.index'))->assertRedirectToRoute('login');
    }

    public function test_owner_can_publish_a_product_with_an_image_visible_on_the_public_pages(): void
    {
        $seller = User::factory()->create(['name' => 'Market Seller']);
        $this->actingAs($seller)->get(route('listings.create'))->assertOk();

        $this->post(route('listings.store'), $this->fields([
            'images' => [$this->image()],
            'user_id' => User::factory()->create()->id,
        ]))->assertRedirect()->assertSessionHas('status', 'Your listing has been published.');

        $listing = Listing::sole();
        $image = $listing->images()->sole();
        $this->assertTrue($listing->user->is($seller));
        $this->assertSame('1250.50', $listing->price);
        $this->assertSame('₹1,250.50', $listing->formattedPrice());
        Storage::disk('public')->assertExists($image->image_path);

        auth()->logout();
        $this->get(route('home'))->assertOk()->assertSeeText($listing->name)->assertSee($image->image_path);
        $this->get(route('listings.show', $listing->slug))->assertOk()
            ->assertSeeText($listing->description)
            ->assertSeeText('Guindy, Chennai, Tamil Nadu, India')
            ->assertSeeText('Market Seller')
            ->assertDontSee($seller->email)
            ->assertDontSeeText('Edit listing');
        $this->get(route('listings.show', 'missing-listing'))->assertNotFound();
    }

    public function test_owner_can_publish_a_service_without_an_image(): void
    {
        $this->actingAs(User::factory()->create())->post(route('listings.store'), $this->fields([
            'type' => 'service',
            'name' => 'Desk assembly',
            'price' => '0',
        ]))->assertRedirect();

        $listing = Listing::sole();
        $this->assertSame('service', $listing->type);
        $this->assertSame('0.00', $listing->price);

        $this->get(route('listings.show', $listing->slug))->assertOk()->assertSee('listing-placeholder.png');
        $this->get(route('home'))->assertOk()->assertSeeText('Desk assembly');
    }

    public function test_required_fields_and_invalid_prices_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('listings.store'))->assertSessionHasErrors(array_keys($this->fields()));

        foreach (['-1', '10000000000', '12.345', '1e2'] as $price) {
            $this->post(route('listings.store'), $this->fields(['price' => $price]))
                ->assertSessionHasErrors('price');
        }

        $this->assertDatabaseEmpty('listings');
    }

    public function test_invalid_category_and_location_relationships_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('listings.store'), $this->fields([
            'category_id' => Category::whereSlug('electronics')->sole()->id,
            'country_id' => Country::create(['name' => 'Test country', 'code' => 'ZZ'])->id,
            'state_id' => State::whereSlug('kerala')->sole()->id,
            'area_id' => City::whereSlug('kochi')->sole()->areas()->firstOrFail()->id,
        ]))->assertSessionHasErrors(['subcategory_id', 'state_id', 'city_id', 'area_id']);

        $this->assertDatabaseEmpty('listings');
    }

    public function test_unsupported_oversized_and_executable_named_uploads_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('listings.store'), $this->fields(['images' => [
            UploadedFile::fake()->createWithContent('fake.png', 'This is not an image.'),
            $this->image('oversized.png')->size(5121),
            $this->image('photo.php'),
        ]]))->assertSessionHasErrors(['images.0', 'images.1', 'images.2']);

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseEmpty('listings');
    }

    public function test_owner_can_update_listing_and_replace_an_image_without_changing_the_slug(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('listings.store'), $this->fields([
            'images' => [$this->image('old.png'), $this->image('retained.png')],
        ]))->assertRedirect();
        $listing = Listing::sole();
        [$removed, $retained] = $listing->images()->get()->all();
        $slug = $listing->slug;

        $this->get(route('listings.edit', $listing))->assertOk()->assertSee($listing->name)
            ->assertSee('data-selected="'.$listing->area_id.'"', false);

        $this->patch(route('listings.update', $listing), $this->fields([
            'name' => 'Updated name',
            'description' => 'Updated description.',
            'price' => '900.00',
            'remove_images' => [$removed->id],
            'images' => [$this->image('replacement.png')],
        ]))->assertRedirectToRoute('listings.show', $slug)->assertSessionHas('status');

        $listing->refresh();
        $this->assertSame($slug, $listing->slug);
        $this->assertSame('Updated description.', $listing->description);
        $this->assertSame('900.00', $listing->price);
        $this->assertCount(2, $listing->images);
        $this->assertTrue($listing->images->first()->is($retained));
        Storage::disk('public')->assertMissing($removed->image_path);
        Storage::disk('public')->assertExists($listing->images->last()->image_path);
    }

    public function test_image_limit_counts_existing_images_and_rejected_updates_clean_up_new_uploads(): void
    {
        $this->actingAs(User::factory()->create())->post(route('listings.store'), $this->fields([
            'images' => array_map(fn ($index) => $this->image("photo-{$index}.png"), range(1, 5)),
        ]))->assertRedirect();
        $listing = Listing::sole();

        $this->patch(route('listings.update', $listing), $this->fields([
            'name' => 'Name must roll back',
            'images' => [$this->image('sixth.png')],
        ]))->assertSessionHasErrors('images');

        $this->assertSame('Oak writing desk', $listing->refresh()->name);
        $this->assertCount(5, $listing->images);
        $this->assertCount(5, Storage::disk('public')->allFiles());
    }

    public function test_owner_can_delete_listing_and_its_image_files(): void
    {
        $this->actingAs(User::factory()->create())->post(route('listings.store'), $this->fields([
            'images' => [$this->image()],
        ]))->assertRedirect();
        $listing = Listing::sole();
        $image = $listing->images()->sole();

        $this->delete(route('listings.destroy', $listing))->assertRedirectToRoute('listings.index')->assertSessionHas('status');

        $this->assertModelMissing($listing);
        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing($image->image_path);
    }

    public function test_other_users_cannot_edit_update_delete_or_see_listing_in_their_dashboard(): void
    {
        $seller = User::factory()->create();
        $other = User::factory()->create();
        $listing = $this->makeListing($seller, 'Oak writing desk');
        $otherListing = $this->makeListing($other, 'Another owner listing');

        $this->actingAs($seller)->get(route('listings.index'))->assertOk()
            ->assertSeeText($listing->name)->assertDontSeeText($otherListing->name);

        $this->get(route('listings.edit', $otherListing))->assertForbidden();
        $this->patch(route('listings.update', $otherListing), $this->fields())->assertForbidden();
        $this->delete(route('listings.destroy', $otherListing))->assertForbidden();
        $this->assertModelExists($otherListing);
    }

    public function test_dependent_routes_return_only_the_selected_parents_options(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::whereSlug('electronics')->sole();
        $india = Country::whereCode('IN')->sole();
        $tamilNadu = State::whereSlug('tamil-nadu')->sole();
        $chennai = City::whereSlug('chennai')->sole();
        $this->getJson(route('listing-options.subcategories', $category))->assertOk()
            ->assertExactJson($category->subcategories()->orderBy('name')->get(['id', 'name'])->toArray());
        $this->getJson(route('listing-options.states', $india))->assertOk()
            ->assertExactJson($india->states()->orderBy('name')->get(['id', 'name'])->toArray());
        $this->getJson(route('listing-options.cities', $tamilNadu))->assertOk()
            ->assertExactJson($tamilNadu->cities()->orderBy('name')->get(['id', 'name'])->toArray());
        $this->getJson(route('listing-options.areas', $chennai))->assertOk()
            ->assertExactJson($chennai->areas()->orderBy('name')->get(['id', 'name'])->toArray());
        $this->getJson("/listing-options/cities/{$chennai->id}-invalid/areas")->assertNotFound();
    }

    public function test_invalid_listing_values_return_field_errors_and_retain_form_input(): void
    {
        $owner = User::factory()->create();
        $invalid = $this->fields([
            'name' => str_repeat('A', 151), 'description' => ' ', 'price' => 'not a price',
            'type' => 'invalid', 'category_id' => 999999,
        ]);

        $this->actingAs($owner)->postJson(route('listings.store'), $invalid)->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'price', 'type', 'category_id']);

        $this->from(route('listings.create'))->post(route('listings.store'), $this->fields(['price' => '-1']))
            ->assertRedirectToRoute('listings.create')->assertSessionHasErrors('price')
            ->assertSessionHasInput('name', 'Oak writing desk');
    }

    public function test_duplicate_names_get_distinct_slugs_and_listing_text_is_escaped(): void
    {
        $this->actingAs(User::factory()->create());
        $description = 'Includes a <strong>wooden stand</strong> & matching trim.';
        foreach (range(1, 2) as $unused) {
            $this->post(route('listings.store'), $this->fields(['description' => $description]))->assertRedirect();
        }

        $this->assertCount(2, Listing::distinct()->pluck('slug'));
        $this->get(route('listings.show', Listing::firstOrFail()->slug))->assertOk()
            ->assertSee($description)->assertDontSee($description, false);
    }

    public function test_new_listing_rejects_more_than_five_images(): void
    {
        $this->actingAs(User::factory()->create())->post(route('listings.store'), $this->fields([
            'images' => array_map(fn ($index) => $this->image("photo-{$index}.png"), range(1, 6)),
        ]))->assertSessionHasErrors('images');

        $this->assertDatabaseEmpty('listings');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_creation_cleans_up_a_file_already_saved_to_disk(): void
    {
        $owner = User::factory()->create();
        $attributes = $this->fields(['category_id' => Category::whereSlug('electronics')->sole()->id]);
        unset($attributes['country_id'], $attributes['state_id']);

        try {
            app(ListingPublisher::class)->publish($owner, $attributes, [$this->image()]);
            $this->fail('The database accepted an incompatible category and subcategory.');
        } catch (QueryException) {
            $this->assertDatabaseEmpty('listings');
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_an_owner_cannot_remove_another_listings_photo(): void
    {
        $owner = User::factory()->create();
        $listing = $this->makeListing($owner, 'Owner listing');
        $other = $this->makeListing(User::factory()->create(), 'Other listing');
        $path = 'listings/other-photo.png';
        Storage::disk('public')->put($path, 'fixture');
        $image = $other->images()->create(['image_path' => $path]);

        $this->actingAs($owner)->patchJson(route('listings.update', $listing), $this->fields([
            'remove_images' => [$image->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors('remove_images.0');

        $this->assertModelExists($image);
        Storage::disk('public')->assertExists($path);
    }

    public function test_out_of_scope_file_metadata_uses_a_placeholder_and_cannot_delete_the_file(): void
    {
        $owner = User::factory()->create();
        $listing = $this->makeListing($owner, 'Owner listing');
        $path = 'documents/receipt.png';
        Storage::disk('public')->put($path, 'fixture');
        $image = $listing->images()->create(['image_path' => $path]);

        $this->get(route('listings.show', $listing->slug))->assertOk()
            ->assertSee('listing-placeholder.png')->assertDontSee($path);
        $this->actingAs($owner)->delete(route('listings.destroy', $listing))->assertRedirectToRoute('listings.index');

        $this->assertModelMissing($image);
        Storage::disk('public')->assertExists($path);
    }

    private function image(string $name = 'listing.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents(public_path('images/listing-placeholder.png')));
    }

    private function fields(array $overrides = []): array
    {
        $category = Category::whereSlug('furniture')->sole();
        $city = City::whereSlug('chennai')->sole();

        return array_replace([
            'type' => 'product',
            'name' => 'Oak writing desk',
            'description' => 'A solid oak desk in good condition.',
            'category_id' => $category->id,
            'subcategory_id' => $category->subcategories()->whereSlug('tables')->sole()->id,
            'country_id' => $city->state->country_id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'area_id' => $city->areas()->whereSlug('guindy')->sole()->id,
            'price' => '1250.50',
        ], $overrides);
    }

    private function makeListing(User $user, string $name): Listing
    {
        $fields = $this->fields(['name' => $name]);
        unset($fields['country_id'], $fields['state_id']);

        return $user->listings()->create($fields + ['slug' => Str::slug($name)]);
    }
}
