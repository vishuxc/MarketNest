<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DemoListingSeeder extends Seeder
{
    public function run(): void
    {
        $examples = [
            ['product', 'Apple iPhone 13', 'electronics', 'Mobiles', 'chennai', 'guindy', 25000, 'Midnight 128 GB model with a slim case and charging cable. The screen is clear with light wear on the case.', 0, 2],
            ['product', 'Dell Inspiron Laptop', 'electronics', 'Laptops', 'bengaluru', 'whitefield', 32000, '15-inch laptop with 16 GB RAM, a 512 GB SSD and the original charger. Suitable for office work and study.', 0, 1],
            ['product', 'Sony Headphones', 'electronics', 'Accessories', 'coimbatore', 'peelamedu', 4500, 'Wireless over-ear headphones with folding hinges and a padded headband. Includes a charging cable and travel pouch.', 1, 1],
            ['product', 'Three-seater Sofa', 'furniture', 'Sofas', 'chennai', 'anna-nagar', 12500, 'Oatmeal fabric sofa with three removable seat cushions and solid wooden legs. Measures approximately 205 cm wide.', 1, 2],
            ['product', 'Wooden Study Table', 'furniture', 'Tables', 'puducherry', 'lawspet', 3500, 'Compact wooden study table with a 95 cm worktop and one drawer. The top has light marks from regular use.', 1, 1],
            ['product', 'Office Chair', 'furniture', 'Chairs', 'bengaluru', 'koramangala', 4200, 'Adjustable mesh task chair with lumbar support, swivel base and working height lever. Suitable for a home office.', 0, 1],
            ['product', 'Honda Activa', 'vehicles', 'Scooters', 'cuddalore', 'neyveli', 45000, 'Well-kept automatic scooter with two keys and recent routine service. A local inspection and document review can be arranged.', 2, 1],
            ['product', 'Royal Enfield Classic 350', 'vehicles', 'Motorcycles', 'chennai', 'velachery', 135000, 'Classic 350 with a black tank, stock exhaust and service records. Minor cosmetic wear around the footrests.', 2, 1],
            ['product', 'Maruti Suzuki Swift', 'vehicles', 'Cars', 'coimbatore', 'gandhipuram', 380000, 'Petrol manual hatchback with working air conditioning and current insurance. Inspection and paperwork review by appointment.', 2, 2],
            ['product', 'Samsung Refrigerator', 'home-appliances', 'Refrigerators', 'kochi', 'edappally', 14000, '253-litre double-door refrigerator with adjustable shelves and a clean freezer compartment. Buyer arranges pickup.', 3, 1],
            ['product', 'LG Washing Machine', 'home-appliances', 'Washing Machines', 'chennai', 'sholinganallur', 11500, '7 kg front-load washer with a quick-wash setting. Includes inlet and drain hoses and can demonstrate a spin cycle.', 3, 1],
            ['product', 'Microwave Oven', 'home-appliances', 'Microwaves', 'bengaluru', 'electronic-city', 3800, '20-litre countertop microwave with a glass turntable, timer and defrost mode. Interior has been cleaned.', 3, 1],
            ['product', 'Nike Running Shoes', 'fashion', 'Footwear', 'chennai', 'guindy', 2500, 'UK size 8 lightweight running shoes with a breathable upper and intact tread. Lightly used for short road runs.', 1, 1],
            ['product', 'Classic Wristwatch', 'fashion', 'Accessories', 'puducherry', 'muthialpet', 1800, 'Analog wristwatch with a simple dial, brown strap and working quartz movement. Small marks on the clasp.', 1, 1],
            ['product', 'Travel Backpack', 'fashion', 'Accessories', 'kochi', 'kakkanad', 1200, '35-litre travel backpack with a padded laptop sleeve, sternum strap and two side pockets. All zippers work.', 1, 1],
            ['product', 'Mountain Bicycle', 'sports', 'Cycling', 'cuddalore', 'chidambaram', 8500, '26-inch hardtail bicycle with front suspension and recently adjusted brakes. Suitable for light trails and commuting.', 2, 1],
            ['product', 'Dumbbell Set', 'sports', 'Fitness Equipment', 'coimbatore', 'peelamedu', 2800, 'Pair of adjustable dumbbells with threaded collars and a mix of weight plates. Suitable for a small home gym.', 0, 1],
            ['product', 'Complete Cricket Kit', 'sports', 'Sports Accessories', 'bengaluru', 'whitefield', 3500, 'Adult practice kit with a bat, batting gloves, pads, helmet and zippered carry bag. Equipment shows normal practice wear.', 2, 1],
            ['service', 'Website Development', 'services', 'IT Services', 'chennai', 'anna-nagar', 5000, 'Starter package for a responsive small-business website with up to three pages and a basic contact form. Hosting is separate.', 0, 1],
            ['service', 'AC Repair and Maintenance', 'services', 'Repair Services', 'bengaluru', 'koramangala', 799, 'One split-AC inspection and basic service visit, including filter cleaning and a cooling check. Parts and gas are quoted separately.', 3, 1],
            ['service', 'Home Cleaning', 'services', 'Home Services', 'coimbatore', 'gandhipuram', 1499, 'Two-hour cleaning session for a kitchen and one bathroom. Standard cleaning supplies and basic equipment are included.', 3, 1],
            ['service', 'Event Photography', 'services', 'Photography', 'puducherry', 'lawspet', 8000, 'Up to three hours of photography for a small private event. Includes a curated set of edited digital photographs.', 0, 2],
            ['service', 'Personal Fitness Training', 'services', 'Fitness Training', 'kochi', 'edappally', 2500, 'Four beginner-friendly one-on-one sessions covering exercise technique and a simple routine. Sessions can be arranged at a local gym.', 2, 1],
            ['service', 'Laptop Repair Service', 'services', 'Repair Services', 'cuddalore', 'neyveli', 699, 'Initial hardware diagnosis and basic troubleshooting for laptops. Repair and replacement parts are quoted after inspection.', 0, 1],
        ];
        $profiles = [
            ['Asha Menon', 'marketnest-demo-asha@example.invalid'],
            ['Nikhil Rao', 'marketnest-demo-nikhil@example.invalid'],
            ['Kavin Kumar', 'marketnest-demo-kavin@example.invalid'],
            ['Meera Iyer', 'marketnest-demo-meera@example.invalid'],
        ];

        $disk = Storage::disk('public');
        $written = [];

        try {
            DB::transaction(function () use ($examples, $profiles, $disk, &$written) {
                $cities = Country::whereCode('IN')->sole()->states()->with('cities.areas')->get()
                    ->flatMap(fn ($state) => $state->cities)->keyBy('slug');
                $sellers = [];
                foreach ($profiles as [$name, $email]) {
                    $name .= ' (Demo)';
                    $seller = User::firstOrCreate(['email' => $email], fn () => [
                        'name' => $name,
                        'password' => Hash::make(Str::random(48)),
                    ]);

                    if ($seller->name !== $name) {
                        throw new RuntimeException('A reserved demo seller address is already in use.');
                    }

                    $sellers[] = $seller;
                }

                foreach ($examples as [$type, $name, $categorySlug, $subcategoryName, $citySlug, $areaSlug, $price, $description, $sellerIndex, $photoCount]) {
                    $imageSlug = Str::slug($name);
                    $slug = 'demo-marketnest-'.$imageSlug;
                    if (Listing::whereSlug($slug)->exists()) {
                        continue;
                    }

                    $category = Category::whereSlug($categorySlug)->sole();
                    $subcategory = $category->subcategories()->firstOrCreate(
                        ['slug' => Str::slug($subcategoryName)],
                        ['name' => $subcategoryName],
                    );
                    $city = $cities->get($citySlug);
                    $area = $city?->areas->firstWhere('slug', $areaSlug);

                    if (! $city || ! $area) {
                        throw new RuntimeException('Run the reference seeders before seeding demo listings.');
                    }

                    $listing = $sellers[$sellerIndex]->listings()->create([
                        'slug' => $slug, 'type' => $type, 'name' => $name,
                        'description' => 'Demonstration listing. '.$description,
                        'category_id' => $category->id, 'subcategory_id' => $subcategory->id,
                        'city_id' => $city->id, 'area_id' => $area->id, 'price' => $price,
                    ]);

                    for ($index = 1; $index <= $photoCount; $index++) {
                        $source = public_path("images/demo/{$imageSlug}-{$index}.webp");
                        if (! is_file($source)) {
                            throw new RuntimeException('A bundled demo illustration is missing.');
                        }

                        $path = 'listings/demo-'.Str::uuid().'.webp';
                        $written[] = $path;
                        if (! $disk->put($path, file_get_contents($source))) {
                            throw new RuntimeException('Unable to copy a demo illustration to public storage.');
                        }

                        $listing->images()->create(['image_path' => $path, 'sort_order' => $index - 1]);
                    }
                }
            });
        } catch (Throwable $exception) {
            $disk->delete($written);

            throw $exception;
        }
    }
}
