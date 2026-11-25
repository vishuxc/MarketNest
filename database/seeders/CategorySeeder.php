<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics' => ['Mobiles', 'Laptops', 'Televisions', 'Accessories'],
            'Vehicles' => ['Cars', 'Motorcycles', 'Scooters'],
            'Furniture' => ['Sofas', 'Beds', 'Tables'],
            'Fashion' => ['Clothing', 'Footwear', 'Accessories'],
            'Home Appliances' => ['Refrigerators', 'Washing Machines', 'Air Conditioners'],
            'Services' => ['IT Services', 'Education', 'Home Services', 'Repair Services'],
            'Sports' => ['Fitness Equipment', 'Sports Accessories'],
        ];

        foreach ($categories as $name => $subcategories) {
            $category = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);

            foreach ($subcategories as $subcategory) {
                $category->subcategories()->firstOrCreate(
                    ['slug' => Str::slug($subcategory)],
                    ['name' => $subcategory],
                );
            }
        }
    }
}
