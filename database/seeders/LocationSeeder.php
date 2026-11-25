<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            'Tamil Nadu' => [
                'Chennai' => ['Guindy', 'Velachery', 'Anna Nagar', 'Sholinganallur'],
                'Coimbatore' => ['Gandhipuram', 'Peelamedu'],
                'Cuddalore' => ['Neyveli', 'Chidambaram'],
            ],
            'Karnataka' => [
                'Bengaluru' => ['Whitefield', 'Electronic City', 'Koramangala'],
            ],
            'Kerala' => [
                'Kochi' => ['Edappally', 'Kakkanad'],
            ],
            'Puducherry' => [
                'Puducherry' => ['Lawspet', 'Muthialpet'],
            ],
        ];

        $india = Country::firstOrCreate(['code' => 'IN'], ['name' => 'India']);

        foreach ($locations as $stateName => $cities) {
            $state = $india->states()->firstOrCreate(['slug' => Str::slug($stateName)], ['name' => $stateName]);

            foreach ($cities as $cityName => $areas) {
                $city = $state->cities()->firstOrCreate(['slug' => Str::slug($cityName)], ['name' => $cityName]);

                foreach ($areas as $area) {
                    $city->areas()->firstOrCreate(['slug' => Str::slug($area)], ['name' => $area]);
                }
            }
        }
    }
}
